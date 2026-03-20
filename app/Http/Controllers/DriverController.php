<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Models\FarmOwner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DriverController extends Controller
{
    use \App\Http\Controllers\Concerns\ResolvesFarmOwner;

    public function index(Request $request)
    {
        $farmOwner = $this->getFarmOwner();
        
        $query = Driver::byFarmOwner($farmOwner->id)
            ->select('id', 'name', 'phone', 'vehicle_type', 'plate_number', 'license_expiry', 'status', 'average_rating');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $drivers = $query->orderBy('name')->paginate(20);

        $stats = [
            'total' => Driver::byFarmOwner($farmOwner->id)->count(),
            'available' => Driver::byFarmOwner($farmOwner->id)->available()->count(),
            'on_delivery' => Driver::byFarmOwner($farmOwner->id)->where('status', 'on_delivery')->count(),
        ];

        return view('farmowner.drivers.index', compact('drivers', 'stats'));
    }

    public function create()
    {
        return view('farmowner.drivers.create');
    }

    public function store(Request $request)
    {
        $farmOwner = $this->getFarmOwner();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'license_number' => 'nullable|string|max:50',
            'license_expiry' => 'nullable|date',
            'vehicle_type' => 'required|in:motorcycle,tricycle,van,truck,pickup',
            'plate_number' => 'nullable|string|max:20',
            'vehicle_capacity_kg' => 'nullable|numeric|min:0',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:20',
            'notes' => 'nullable|string',
        ]);

        $validated['farm_owner_id'] = $farmOwner->id;

        Driver::create($validated);

        return redirect()->route('drivers.index')->with('success', 'Driver added.');
    }

    public function show(Driver $driver)
    {
        $farmOwner = $this->getFarmOwner();
        abort_if($driver->farm_owner_id !== $farmOwner->id, 403);

        $driver->load(['deliveries' => fn($q) => $q->latest('created_at')->limit(20)]);

        $stats = [
            'total_deliveries' => $driver->total_deliveries,
            'completed_deliveries' => $driver->completed_deliveries,
            'success_rate' => $driver->total_deliveries > 0 
                ? round(($driver->completed_deliveries / $driver->total_deliveries) * 100, 1) 
                : 0,
        ];

        return view('farmowner.drivers.show', compact('driver', 'stats'));
    }

    public function edit(Driver $driver)
    {
        $farmOwner = $this->getFarmOwner();
        abort_if($driver->farm_owner_id !== $farmOwner->id, 403);

        return view('farmowner.drivers.edit', compact('driver'));
    }

    public function update(Request $request, Driver $driver)
    {
        $farmOwner = $this->getFarmOwner();
        abort_if($driver->farm_owner_id !== $farmOwner->id, 403);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'license_number' => 'nullable|string|max:50',
            'license_expiry' => 'nullable|date',
            'vehicle_type' => 'required|in:motorcycle,tricycle,van,truck,pickup',
            'plate_number' => 'nullable|string|max:20',
            'vehicle_capacity_kg' => 'nullable|numeric|min:0',
            'status' => 'required|in:available,on_delivery,off_duty,suspended',
            'notes' => 'nullable|string',
        ]);

        $driver->update($validated);

        return redirect()->route('drivers.show', $driver)->with('success', 'Driver updated.');
    }

    public function destroy(Driver $driver)
    {
        $farmOwner = $this->getFarmOwner();
        abort_if($driver->farm_owner_id !== $farmOwner->id, 403);

        $driver->delete();

        return redirect()->route('drivers.index')->with('success', 'Driver removed.');
    }
}
