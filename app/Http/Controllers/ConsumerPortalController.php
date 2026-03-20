<?php

namespace App\Http\Controllers;

use App\Models\Delivery;
use App\Models\Notification;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ConsumerPortalController extends Controller
{
    private function ensureMarketplaceUser(): void
    {
        $role = (string) (Auth::user()?->role ?? '');

        if (!in_array($role, ['consumer', 'client'], true)) {
            abort(403, 'This page is only available for marketplace customers.');
        }
    }

    public function editProfile(Request $request): View
    {
        $this->ensureMarketplaceUser();

        return view('marketplace.profile-edit', [
            'user' => $request->user(),
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $this->ensureMarketplaceUser();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:30',
            'location' => 'nullable|string|max:255',
        ]);

        $request->user()->update($validated);

        return redirect()->route('marketplace.profile.edit')->with('success', 'Profile updated successfully.');
    }

    public function notifications(): View
    {
        $this->ensureMarketplaceUser();

        $user = Auth::user();

        $notifications = Notification::forUser($user->id)
            ->orderByDesc('created_at')
            ->paginate(15);

        $orders = Order::where('consumer_id', $user->id)
            ->with('farmOwner:id,user_id,farm_name')
            ->latest('created_at')
            ->get(['id', 'farm_owner_id', 'order_number', 'status', 'created_at']);

        // Mark unread notifications as read when user opens inbox.
        Notification::forUser($user->id)
            ->unread()
            ->update(['is_read' => true, 'read_at' => now()]);

        return view('marketplace.notifications', compact('notifications', 'orders'));
    }

    public function storeComplaint(Request $request): RedirectResponse
    {
        $this->ensureMarketplaceUser();

        $user = Auth::user();

        $validated = $request->validate([
            'order_id' => 'required|integer|exists:orders,id',
            'subject' => 'required|string|max:120',
            'message' => 'required|string|max:1500',
        ]);

        $order = Order::where('id', $validated['order_id'])
            ->where('consumer_id', $user->id)
            ->with('farmOwner:id,user_id,farm_name')
            ->firstOrFail();

        if (!$order->farmOwner || !$order->farmOwner->user_id) {
            return redirect()->back()->with('error', 'Farm owner account is not available for this order.');
        }

        // Notification to farm owner about customer complaint.
        Notification::create([
            'user_id' => $order->farmOwner->user_id,
            'title' => 'Customer Complaint: ' . $validated['subject'],
            'message' => "Order {$order->order_number}: {$validated['message']}",
            'type' => 'alert',
            'channel' => 'in_app',
            'data' => [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'consumer_id' => $user->id,
                'consumer_name' => $user->name,
                'complaint_subject' => $validated['subject'],
            ],
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        // Acknowledge the customer in their own inbox.
        Notification::create([
            'user_id' => $user->id,
            'title' => 'Complaint Sent',
            'message' => "Your complaint for order {$order->order_number} was sent to {$order->farmOwner->farm_name}.",
            'type' => 'system',
            'channel' => 'in_app',
            'data' => [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
            ],
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        return redirect()->route('marketplace.notifications')->with('success', 'Complaint sent to farm owner successfully.');
    }

    public function ratings(): View
    {
        $this->ensureMarketplaceUser();

        $deliveries = Delivery::where('status', 'delivered')
            ->whereHas('order', function ($query): void {
                $query->where('consumer_id', Auth::id());
            })
            ->with([
                'order:id,order_number,consumer_id,farm_owner_id,total_amount,delivered_at',
                'order.farmOwner:id,farm_name',
            ])
            ->latest('delivered_at')
            ->paginate(12);

        return view('marketplace.ratings', compact('deliveries'));
    }

    public function storeRating(Request $request, Delivery $delivery): RedirectResponse
    {
        $this->ensureMarketplaceUser();

        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'feedback' => 'nullable|string|max:500',
        ]);

        $delivery->load('order', 'farmOwner');

        if (!$delivery->order || $delivery->order->consumer_id !== Auth::id()) {
            abort(403, 'You can only rate your own delivered orders.');
        }

        if ($delivery->status !== 'delivered') {
            return redirect()->route('marketplace.ratings')->with('error', 'Only delivered orders can be rated.');
        }

        $delivery->rateDelivery((float) $validated['rating'], $validated['feedback'] ?? null);

        if ($delivery->farmOwner) {
            $average = Delivery::where('farm_owner_id', $delivery->farm_owner_id)
                ->whereNotNull('rating')
                ->avg('rating');

            $delivery->farmOwner->update([
                'average_rating' => round((float) ($average ?? 0), 2),
            ]);
        }

        return redirect()->route('marketplace.ratings')->with('success', 'Thank you! Your rating was submitted.');
    }
}
