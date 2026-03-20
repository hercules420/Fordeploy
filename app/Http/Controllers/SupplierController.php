<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Models\FarmOwner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SupplierController extends Controller
{
    use \App\Http\Controllers\Concerns\ResolvesFarmOwner;

    public function index(Request $request)
    {
        $farmOwner = $this->getFarmOwner();
        
        $query = Supplier::byFarmOwner($farmOwner->id)
            ->select('id', 'company_name', 'contact_person', 'phone', 'category', 'outstanding_balance', 'status');

        if ($request->filled('category')) {
            $query->byCategory($request->category);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $suppliers = $query->orderBy('company_name')->paginate(20);

        $stats = [
            'total' => Supplier::byFarmOwner($farmOwner->id)->count(),
            'active' => Supplier::byFarmOwner($farmOwner->id)->active()->count(),
            'total_outstanding' => Supplier::byFarmOwner($farmOwner->id)->sum('outstanding_balance'),
        ];

        return view('farmowner.suppliers.index', compact('suppliers', 'stats'));
    }

    public function create()
    {
        return view('farmowner.suppliers.create');
    }

    public function store(Request $request)
    {
        $farmOwner = $this->getFarmOwner();

        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'mobile' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'province' => 'nullable|string|max:100',
            'category' => 'required|in:feeds,vitamins,vaccines,equipment,chicks,general',
            'payment_terms' => 'nullable|string|max:100',
            'credit_limit' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $validated['farm_owner_id'] = $farmOwner->id;

        Supplier::create($validated);

        return redirect()->route('suppliers.index')->with('success', 'Supplier added successfully.');
    }

    public function show(Supplier $supplier)
    {
        $farmOwner = $this->getFarmOwner();
        abort_if($supplier->farm_owner_id !== $farmOwner->id, 403);

        $supplier->load([
            'supplyItems' => fn($q) => $q->select('id', 'supplier_id', 'name', 'category', 'quantity_on_hand')->limit(10),
            'expenses' => fn($q) => $q->select('id', 'supplier_id', 'description', 'total_amount', 'expense_date', 'payment_status')->latest('expense_date')->limit(10),
        ]);

        return view('farmowner.suppliers.show', compact('supplier'));
    }

    public function edit(Supplier $supplier)
    {
        $farmOwner = $this->getFarmOwner();
        abort_if($supplier->farm_owner_id !== $farmOwner->id, 403);

        return view('farmowner.suppliers.edit', compact('supplier'));
    }

    public function update(Request $request, Supplier $supplier)
    {
        $farmOwner = $this->getFarmOwner();
        abort_if($supplier->farm_owner_id !== $farmOwner->id, 403);

        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'mobile' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'province' => 'nullable|string|max:100',
            'category' => 'required|in:feeds,vitamins,vaccines,equipment,chicks,general',
            'payment_terms' => 'nullable|string|max:100',
            'credit_limit' => 'nullable|numeric|min:0',
            'status' => 'required|in:active,inactive,blacklisted',
            'notes' => 'nullable|string',
        ]);

        $supplier->update($validated);

        return redirect()->route('suppliers.show', $supplier)->with('success', 'Supplier updated.');
    }

    public function destroy(Supplier $supplier)
    {
        $farmOwner = $this->getFarmOwner();
        abort_if($supplier->farm_owner_id !== $farmOwner->id, 403);

        $supplier->delete();

        return redirect()->route('suppliers.index')->with('success', 'Supplier removed.');
    }
}
