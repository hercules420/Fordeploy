<?php

namespace App\Http\Controllers;

use App\Models\Delivery;
use App\Models\Driver;
use App\Models\Order;
use App\Models\FarmOwner;
use App\Models\IncomeRecord;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class DeliveryController extends Controller
{
    use \App\Http\Controllers\Concerns\ResolvesFarmOwner;

    private function statsCacheKey(int $farmOwnerId): string
    {
        return "farm_{$farmOwnerId}_delivery_stats";
    }

    private function clearStatsCache(int $farmOwnerId): void
    {
        Cache::forget($this->statsCacheKey($farmOwnerId));
    }

    public function index(Request $request)
    {
        $farmOwner = $this->getFarmOwner();
        
        $query = Delivery::byFarmOwner($farmOwner->id)
            ->with(['order:id,order_number', 'driver:id,name,phone'])
            ->select('id', 'tracking_number', 'order_id', 'driver_id', 'recipient_name', 'delivery_address', 'scheduled_date', 'status', 'cod_amount', 'cod_collected');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date')) {
            $query->whereDate('scheduled_date', $request->date);
        }

        $deliveries = $query->latest('scheduled_date')->paginate(20);

        $stats = Cache::remember($this->statsCacheKey($farmOwner->id), 120, function () use ($farmOwner) {
            $todayStart = now()->startOfDay()->toDateTimeString();
            $tomorrowStart = now()->startOfDay()->addDay()->toDateTimeString();

            $aggregate = Delivery::byFarmOwner($farmOwner->id)
                ->selectRaw("SUM(CASE WHEN status IN ('preparing', 'packed', 'assigned') THEN 1 ELSE 0 END) as pending")
                ->selectRaw("SUM(CASE WHEN status = 'out_for_delivery' THEN 1 ELSE 0 END) as dispatched")
                ->selectRaw(
                    "SUM(CASE WHEN status IN ('delivered', 'completed') AND delivered_at >= ? AND delivered_at < ? THEN 1 ELSE 0 END) as delivered_today",
                    [$todayStart, $tomorrowStart]
                )
                ->selectRaw("COALESCE(SUM(CASE WHEN cod_collected = false AND cod_amount > 0 THEN cod_amount ELSE 0 END), 0) as cod_pending")
                ->first();

            return [
                'pending' => (int) ($aggregate->pending ?? 0),
                'dispatched' => (int) ($aggregate->dispatched ?? 0),
                'delivered_today' => (int) ($aggregate->delivered_today ?? 0),
                'cod_pending' => (float) ($aggregate->cod_pending ?? 0),
            ];
        });

        return view('farmowner.deliveries.index', compact('deliveries', 'stats'));
    }

    public function create()
    {
        $farmOwner = $this->getFarmOwner();
        
        $drivers = Driver::byFarmOwner($farmOwner->id)->available()->select('id', 'name', 'vehicle_type')->get();
        $orders = Order::where('farm_owner_id', $farmOwner->id)
            ->whereDoesntHave('delivery')
            ->where('status', 'confirmed')
            ->select('id', 'order_number')
            ->get();

        return view('farmowner.deliveries.create', compact('drivers', 'orders'));
    }

    public function store(Request $request)
    {
        $farmOwner = $this->getFarmOwner();

        $validated = $request->validate([
            'order_id' => 'nullable|exists:orders,id',
            'driver_id' => [
                'nullable',
                Rule::exists('drivers', 'id')->where(function ($query) use ($farmOwner) {
                    $query->where('farm_owner_id', $farmOwner->id)->where('status', 'available');
                }),
            ],
            'recipient_name' => 'required|string|max:255',
            'recipient_phone' => 'required|string|max:20',
            'delivery_address' => 'required|string',
            'city' => 'nullable|string|max:100',
            'province' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'scheduled_date' => 'required|date',
            'scheduled_time_from' => 'nullable|date_format:H:i',
            'scheduled_time_to' => 'nullable|date_format:H:i',
            'delivery_fee' => 'nullable|numeric|min:0',
            'cod_amount' => 'nullable|numeric|min:0',
            'special_instructions' => 'nullable|string',
            'delivery_notes' => 'nullable|string',
        ]);

        $validated['farm_owner_id'] = $farmOwner->id;
        $validated['assigned_by'] = Auth::id();

        $delivery = Delivery::create($validated);

        // Staff assigns driver manually when needed.
        if ($delivery->driver_id) {
            $delivery->assignDriver($delivery->driver_id, Auth::id());
        }

        if ($delivery->order && in_array((string) $delivery->order->status, ['confirmed', 'pending'], true)) {
            $delivery->order->update(['status' => 'processing']);
        }

        $this->clearStatsCache($farmOwner->id);

        return redirect()->route('deliveries.index')->with('success', 'Delivery created.');
    }

    public function show(Delivery $delivery)
    {
        $farmOwner = $this->getFarmOwner();
        abort_if($delivery->farm_owner_id !== $farmOwner->id, 403);

        $delivery->load(['order', 'driver', 'assignedBy']);
        $availableDrivers = Driver::byFarmOwner($farmOwner->id)
            ->available()
            ->select('id', 'name', 'vehicle_type')
            ->get();

        return view('farmowner.deliveries.show', compact('delivery', 'availableDrivers'));
    }

    public function edit(Delivery $delivery)
    {
        $farmOwner = $this->getFarmOwner();
        abort_if($delivery->farm_owner_id !== $farmOwner->id, 403);
        abort_if($delivery->status === 'delivered', 403, 'Cannot edit delivered orders.');

        $drivers = Driver::byFarmOwner($farmOwner->id)->available()->select('id', 'name', 'vehicle_type')->get();

        return view('farmowner.deliveries.edit', compact('delivery', 'drivers'));
    }

    public function update(Request $request, Delivery $delivery)
    {
        $farmOwner = $this->getFarmOwner();
        abort_if($delivery->farm_owner_id !== $farmOwner->id, 403);

        $validated = $request->validate([
            'driver_id' => 'nullable|exists:drivers,id',
            'recipient_phone' => 'required|string|max:20',
            'delivery_address' => 'required|string',
            'scheduled_date' => 'required|date',
            'scheduled_time_from' => 'nullable|date_format:H:i',
            'scheduled_time_to' => 'nullable|date_format:H:i',
            'delivery_fee' => 'nullable|numeric|min:0',
            'special_instructions' => 'nullable|string',
            'delivery_notes' => 'nullable|string',
        ]);

        $delivery->update($validated);
        $this->clearStatsCache($farmOwner->id);

        return redirect()->route('deliveries.show', $delivery)->with('success', 'Delivery updated.');
    }

    public function assignDriver(Request $request, Delivery $delivery)
    {
        $farmOwner = $this->getFarmOwner();
        abort_if($delivery->farm_owner_id !== $farmOwner->id, 403);

        if (!in_array((string) $delivery->status, ['packed', 'preparing'], true)) {
            return redirect()->route('deliveries.show', $delivery)
                ->with('error', 'Driver can only be assigned while delivery is in preparing/packed stage.');
        }

        $validated = $request->validate([
            'driver_id' => [
                'required',
                Rule::exists('drivers', 'id')->where(function ($query) use ($farmOwner) {
                    $query->where('farm_owner_id', $farmOwner->id)->where('status', 'available');
                }),
            ],
        ]);

        $delivery->assignDriver((int) $validated['driver_id'], Auth::id());
        $this->clearStatsCache($farmOwner->id);

        return redirect()->route('deliveries.show', $delivery)->with('success', 'Driver assigned.');
    }

    public function markPacked(Delivery $delivery)
    {
        $farmOwner = $this->getFarmOwner();
        abort_if($delivery->farm_owner_id !== $farmOwner->id, 403);

        if ($delivery->status !== 'preparing') {
            return redirect()->route('deliveries.show', $delivery)
                ->with('error', 'Only preparing deliveries can be marked as packed.');
        }

        $delivery->markPacked();

        if ($delivery->order && in_array((string) $delivery->order->status, ['confirmed', 'processing'], true)) {
            // "ready_for_pickup" is used as packed/ready stage in the order table lifecycle.
            $delivery->order->update(['status' => 'ready_for_pickup']);
        }

        $this->clearStatsCache($farmOwner->id);

        return redirect()->route('deliveries.show', $delivery)->with('success', 'Delivery marked as packed.');
    }

    public function dispatch(Delivery $delivery)
    {
        $farmOwner = $this->getFarmOwner();
        abort_if($delivery->farm_owner_id !== $farmOwner->id, 403);
        abort_unless($delivery->driver_id, 403, 'Assign driver first.');

        if (!in_array((string) $delivery->status, ['assigned', 'packed'], true)) {
            return redirect()->route('deliveries.show', $delivery)
                ->with('error', 'Only packed/assigned deliveries can be dispatched.');
        }

        $delivery->dispatch();

        if ($delivery->order) {
            $delivery->order->update(['status' => 'shipped']);
        }

        // Delivery tracking update is notification-only and sent when status turns out-for-delivery.
        if ($delivery->order && $delivery->order->consumer_id) {
            Notification::create([
                'user_id' => $delivery->order->consumer_id,
                'title' => 'Out for Delivery',
                'message' => "Your order {$delivery->order->order_number} is now out for delivery.",
                'type' => 'delivery_update',
                'channel' => 'in_app',
                'data' => [
                    'order_id' => $delivery->order->id,
                    'order_number' => $delivery->order->order_number,
                    'delivery_id' => $delivery->id,
                    'tracking_number' => $delivery->tracking_number,
                    'status' => 'out_for_delivery',
                ],
                'status' => 'sent',
                'sent_at' => now(),
            ]);
        }

        $this->clearStatsCache($farmOwner->id);

        return redirect()->route('deliveries.show', $delivery)->with('success', 'Delivery dispatched.');
    }

    public function markDelivered(Request $request, Delivery $delivery)
    {
        $farmOwner = $this->getFarmOwner();
        abort_if($delivery->farm_owner_id !== $farmOwner->id, 403);

        $validated = $request->validate([
            'cod_collected' => 'nullable|boolean',
            'proof_of_delivery' => 'nullable|string|max:255',
            'delivery_notes' => 'nullable|string',
        ]);

        $delivery->markDelivered($validated['proof_of_delivery'] ?? null);

        if ($delivery->order) {
            $delivery->order->markAsDelivered();
            IncomeRecord::where('order_id', $delivery->order->id)
                ->update(['payment_status' => 'received']);
            Cache::forget("farm_{$farmOwner->id}_income_stats");
        }

        // Update COD collection status if applicable
        if (isset($validated['cod_collected'])) {
            $delivery->update(['cod_collected' => $validated['cod_collected']]);
        }

        $this->clearStatsCache($farmOwner->id);

        return redirect()->route('deliveries.show', $delivery)->with('success', 'Delivery completed.');
    }

    public function markCompleted(Delivery $delivery)
    {
        $farmOwner = $this->getFarmOwner();
        abort_if($delivery->farm_owner_id !== $farmOwner->id, 403);

        if ($delivery->status !== 'delivered') {
            return redirect()->route('deliveries.show', $delivery)
                ->with('error', 'Only delivered records can be marked as completed.');
        }

        $delivery->markCompleted();

        if ($delivery->order) {
            $delivery->order->update(['status' => 'completed']);
        }

        $this->clearStatsCache($farmOwner->id);

        return redirect()->route('deliveries.show', $delivery)->with('success', 'Delivery record completed.');
    }

    public function markFailed(Request $request, Delivery $delivery)
    {
        $farmOwner = $this->getFarmOwner();
        abort_if($delivery->farm_owner_id !== $farmOwner->id, 403);

        $validated = $request->validate([
            'failure_reason' => 'required|string|max:255',
        ]);

        $delivery->markFailed($validated['failure_reason']);
        $this->clearStatsCache($farmOwner->id);

        return redirect()->route('deliveries.show', $delivery)->with('success', 'Delivery marked as failed.');
    }

    public function schedule()
    {
        $farmOwner = $this->getFarmOwner();

        $today = Delivery::byFarmOwner($farmOwner->id)
            ->scheduledToday()
            ->with('driver:id,name')
            ->orderBy('scheduled_time_from')
            ->get();

        $tomorrow = Delivery::byFarmOwner($farmOwner->id)
            ->whereDate('scheduled_date', today()->addDay())
            ->with('driver:id,name')
            ->orderBy('scheduled_time_from')
            ->get();

        $unscheduled = Delivery::byFarmOwner($farmOwner->id)
            ->whereIn('status', ['preparing', 'packed'])
            ->whereNull('driver_id')
            ->get();

        $drivers = Driver::byFarmOwner($farmOwner->id)->available()->get();

        return view('farmowner.deliveries.schedule', compact('today', 'tomorrow', 'unscheduled', 'drivers'));
    }
}
