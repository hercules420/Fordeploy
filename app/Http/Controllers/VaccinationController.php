<?php

namespace App\Http\Controllers;

use App\Models\Vaccination;
use App\Models\Flock;
use App\Models\FarmOwner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class VaccinationController extends Controller
{
    use \App\Http\Controllers\Concerns\ResolvesFarmOwner;

    public function index(Request $request)
    {
        $farmOwner = $this->getFarmOwner();
        
        $query = Vaccination::byFarmOwner($farmOwner->id)
            ->with(['flock:id,batch_name', 'administeredBy:id,name'])
            ->select('id', 'flock_id', 'administered_by', 'type', 'name', 'date_administered', 'next_due_date', 'status', 'cost');

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $vaccinations = $query->latest('date_administered')->paginate(20);

        $stats = [
            'total' => Vaccination::byFarmOwner($farmOwner->id)->count(),
            'upcoming' => Vaccination::byFarmOwner($farmOwner->id)->upcoming()->count(),
            'overdue' => Vaccination::byFarmOwner($farmOwner->id)->overdue()->count(),
            'total_cost' => Vaccination::byFarmOwner($farmOwner->id)->sum('cost'),
        ];

        $flocks = Flock::byFarmOwner($farmOwner->id)->active()->select('id', 'batch_name')->get();

        return view('farmowner.vaccinations.index', compact('vaccinations', 'stats', 'flocks'));
    }

    public function create()
    {
        $farmOwner = $this->getFarmOwner();
        $flocks = Flock::byFarmOwner($farmOwner->id)->active()->select('id', 'batch_name', 'current_count')->get();

        return view('farmowner.vaccinations.create', compact('flocks'));
    }

    public function store(Request $request)
    {
        $farmOwner = $this->getFarmOwner();

        $validated = $request->validate([
            'flock_id' => 'nullable|exists:flocks,id',
            'type' => 'required|in:vaccine,medication,supplement,dewormer',
            'name' => 'required|string|max:255',
            'brand' => 'nullable|string|max:100',
            'batch_number' => 'nullable|string|max:100',
            'dosage' => 'required|numeric|min:0',
            'dosage_unit' => 'required|string|max:20',
            'administration_method' => 'required|in:drinking_water,injection,spray,eye_drop,feed_mix',
            'date_administered' => 'required|date',
            'next_due_date' => 'nullable|date|after:date_administered',
            'birds_treated' => 'nullable|integer|min:0',
            'cost' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $validated['farm_owner_id'] = $farmOwner->id;
        $validated['administered_by'] = Auth::id();
        $validated['status'] = 'completed';

        Vaccination::create($validated);

        return redirect()->route('vaccinations.index')->with('success', 'Vaccination record created.');
    }

    public function show(Vaccination $vaccination)
    {
        $farmOwner = $this->getFarmOwner();
        abort_if($vaccination->farm_owner_id !== $farmOwner->id, 403);

        $vaccination->load(['flock', 'administeredBy']);

        return view('farmowner.vaccinations.show', compact('vaccination'));
    }

    public function edit(Vaccination $vaccination)
    {
        $farmOwner = $this->getFarmOwner();
        abort_if($vaccination->farm_owner_id !== $farmOwner->id, 403);

        $flocks = Flock::byFarmOwner($farmOwner->id)->active()->select('id', 'batch_name')->get();

        return view('farmowner.vaccinations.edit', compact('vaccination', 'flocks'));
    }

    public function update(Request $request, Vaccination $vaccination)
    {
        $farmOwner = $this->getFarmOwner();
        abort_if($vaccination->farm_owner_id !== $farmOwner->id, 403);

        $validated = $request->validate([
            'next_due_date' => 'nullable|date',
            'status' => 'required|in:scheduled,completed,missed,cancelled',
            'notes' => 'nullable|string',
            'side_effects' => 'nullable|string',
        ]);

        $vaccination->update($validated);

        return redirect()->route('vaccinations.show', $vaccination)->with('success', 'Vaccination updated.');
    }

    public function destroy(Vaccination $vaccination)
    {
        $farmOwner = $this->getFarmOwner();
        abort_if($vaccination->farm_owner_id !== $farmOwner->id, 403);

        $vaccination->delete();

        return redirect()->route('vaccinations.index')->with('success', 'Vaccination record deleted.');
    }

    public function upcoming()
    {
        $farmOwner = $this->getFarmOwner();

        $upcoming = Vaccination::byFarmOwner($farmOwner->id)
            ->upcoming(14)
            ->with(['flock:id,batch_name'])
            ->orderBy('next_due_date')
            ->get();

        $overdue = Vaccination::byFarmOwner($farmOwner->id)
            ->overdue()
            ->with(['flock:id,batch_name'])
            ->orderBy('next_due_date')
            ->get();

        return view('farmowner.vaccinations.upcoming', compact('upcoming', 'overdue'));
    }
}
