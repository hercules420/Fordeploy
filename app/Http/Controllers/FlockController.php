<?php

namespace App\Http\Controllers;

use App\Models\Flock;
use App\Models\FlockRecord;
use App\Models\FarmOwner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class FlockController extends Controller
{
    use \App\Http\Controllers\Concerns\ResolvesFarmOwner;

    public function index()
    {
        $farmOwner = $this->getFarmOwner();
        
        $flocks = Flock::byFarmOwner($farmOwner->id)
            ->select('id', 'batch_name', 'breed_type', 'flock_type', 'current_count', 'mortality_count', 'status', 'date_acquired', 'age_weeks')
            ->latest('created_at')
            ->paginate(20);

        $stats = Cache::remember("farm_{$farmOwner->id}_flock_stats", 300, function () use ($farmOwner) {
            return [
                'total_flocks' => Flock::byFarmOwner($farmOwner->id)->active()->count(),
                'total_birds' => Flock::byFarmOwner($farmOwner->id)->active()->sum('current_count'),
                'total_mortality' => Flock::byFarmOwner($farmOwner->id)->sum('mortality_count'),
                'layers' => Flock::byFarmOwner($farmOwner->id)->active()->byType('layer')->sum('current_count'),
                'broilers' => Flock::byFarmOwner($farmOwner->id)->active()->byType('broiler')->sum('current_count'),
            ];
        });

        return view('farmowner.flocks.index', compact('flocks', 'stats'));
    }

    public function create()
    {
        return view('farmowner.flocks.create');
    }

    public function store(Request $request)
    {
        $farmOwner = $this->getFarmOwner();

        $validated = $request->validate([
            'batch_name' => 'required|string|max:100',
            'breed_type' => 'required|string|max:100',
            'flock_type' => 'required|in:broiler,layer,breeder,native,fighting_cock',
            'initial_count' => 'required|integer|min:1',
            'date_acquired' => 'required|date',
            'age_weeks' => 'nullable|integer|min:0',
            'source' => 'nullable|string|max:255',
            'acquisition_cost' => 'nullable|numeric|min:0',
            'housing_location' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $validated['farm_owner_id'] = $farmOwner->id;
        $validated['current_count'] = $validated['initial_count'];

        Flock::create($validated);
        Cache::forget("farm_{$farmOwner->id}_flock_stats");

        return redirect()->route('flocks.index')->with('success', 'Flock batch created successfully.');
    }

    public function show(Flock $flock)
    {
        $farmOwner = $this->getFarmOwner();
        abort_if($flock->farm_owner_id !== $farmOwner->id, 403);

        $flock->load(['records' => fn($q) => $q->latest('record_date')->limit(30)]);
        
        $recordStats = FlockRecord::byFlock($flock->id)
            ->selectRaw('SUM(eggs_collected) as total_eggs, SUM(mortality_today) as total_mortality, AVG(average_weight_kg) as avg_weight')
            ->first();

        return view('farmowner.flocks.show', compact('flock', 'recordStats'));
    }

    public function edit(Flock $flock)
    {
        $farmOwner = $this->getFarmOwner();
        abort_if($flock->farm_owner_id !== $farmOwner->id, 403);

        return view('farmowner.flocks.edit', compact('flock'));
    }

    public function update(Request $request, Flock $flock)
    {
        $farmOwner = $this->getFarmOwner();
        abort_if($flock->farm_owner_id !== $farmOwner->id, 403);

        $validated = $request->validate([
            'batch_name' => 'required|string|max:100',
            'breed_type' => 'required|string|max:100',
            'age_weeks' => 'nullable|integer|min:0',
            'housing_location' => 'nullable|string|max:255',
            'status' => 'required|in:active,sold,culled,transferred',
            'notes' => 'nullable|string',
        ]);

        $flock->update($validated);
        Cache::forget("farm_{$farmOwner->id}_flock_stats");

        return redirect()->route('flocks.show', $flock)->with('success', 'Flock updated successfully.');
    }

    public function destroy(Flock $flock)
    {
        $farmOwner = $this->getFarmOwner();
        abort_if($flock->farm_owner_id !== $farmOwner->id, 403);

        $flock->delete();
        Cache::forget("farm_{$farmOwner->id}_flock_stats");

        return redirect()->route('flocks.index')->with('success', 'Flock removed successfully.');
    }

    // Daily Record Management
    public function addRecord(Request $request, Flock $flock)
    {
        $farmOwner = $this->getFarmOwner();
        abort_if($flock->farm_owner_id !== $farmOwner->id, 403);

        $validated = $request->validate([
            'record_date' => 'required|date',
            'mortality_today' => 'nullable|integer|min:0',
            'mortality_cause' => 'nullable|string|max:255',
            'feed_consumed_kg' => 'nullable|numeric|min:0',
            'water_consumed_liters' => 'nullable|numeric|min:0',
            'eggs_collected' => 'nullable|integer|min:0',
            'eggs_broken' => 'nullable|integer|min:0',
            'average_weight_kg' => 'nullable|numeric|min:0',
            'health_status' => 'required|in:excellent,good,fair,poor,critical',
            'health_notes' => 'nullable|string',
            'remarks' => 'nullable|string',
        ]);

        $validated['flock_id'] = $flock->id;
        $validated['recorded_by'] = Auth::id();

        FlockRecord::updateOrCreate(
            ['flock_id' => $flock->id, 'record_date' => $validated['record_date']],
            $validated
        );

        // Update flock mortality
        if (isset($validated['mortality_today']) && $validated['mortality_today'] > 0) {
            $flock->recordMortality($validated['mortality_today'], $validated['mortality_cause'] ?? null);
        }

        return redirect()->route('flocks.show', $flock)->with('success', 'Daily record saved.');
    }
}
