<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Supplier;
use App\Models\FarmOwner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class ExpenseController extends Controller
{
    use \App\Http\Controllers\Concerns\ResolvesFarmOwner;

    public function index(Request $request)
    {
        $farmOwner = $this->getFarmOwner();
        
        $query = Expense::byFarmOwner($farmOwner->id)
            ->with(['supplier:id,company_name', 'recordedBy:id,name'])
            ->select('id', 'expense_number', 'supplier_id', 'recorded_by', 'category', 'description', 'total_amount', 'expense_date', 'payment_status');

        if ($request->filled('category')) {
            $query->byCategory($request->category);
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        if ($request->filled('month')) {
            $date = Carbon::parse($request->month);
            $monthStart = $date->copy()->startOfMonth()->toDateString();
            $nextMonthStart = $date->copy()->startOfMonth()->addMonth()->toDateString();

            $query->where('expense_date', '>=', $monthStart)
                ->where('expense_date', '<', $nextMonthStart);
        }

        $expenses = $query->latest('expense_date')->paginate(20);

        $stats = Cache::remember("farm_{$farmOwner->id}_expense_stats", 300, function () use ($farmOwner) {
            $thisMonthStart = now()->startOfMonth()->toDateString();
            $nextMonthStart = now()->startOfMonth()->addMonth()->toDateString();

            return [
                'total_this_month' => Expense::byFarmOwner($farmOwner->id)
                    ->where('expense_date', '>=', $thisMonthStart)
                    ->where('expense_date', '<', $nextMonthStart)
                    ->sum('total_amount'),
                'unpaid' => Expense::byFarmOwner($farmOwner->id)->whereIn('payment_status', ['pending', 'partial', 'overdue'])->sum('total_amount'),
                'by_category' => Expense::byFarmOwner($farmOwner->id)
                    ->where('expense_date', '>=', $thisMonthStart)
                    ->where('expense_date', '<', $nextMonthStart)
                    ->selectRaw('category, SUM(total_amount) as total')
                    ->groupBy('category')
                    ->pluck('total', 'category'),
            ];
        });

        return view('farmowner.expenses.index', compact('expenses', 'stats'));
    }

    public function create()
    {
        $farmOwner = $this->getFarmOwner();
        $suppliers = Supplier::byFarmOwner($farmOwner->id)->active()->select('id', 'company_name')->get();

        return view('farmowner.expenses.create', compact('suppliers'));
    }

    public function store(Request $request)
    {
        $farmOwner = $this->getFarmOwner();

        $validated = $request->validate([
            'supplier_id' => 'nullable|exists:suppliers,id',
            'category' => 'required|in:feeds,vaccines,medications,utilities,labor,equipment,maintenance,transportation,marketing,taxes,insurance,miscellaneous',
            'description' => 'required|string|max:255',
            'amount' => 'nullable|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
            'expense_date' => 'required|date',
            'payment_method' => 'nullable|in:cash,bank_transfer,check,credit,gcash,maya',
            'payment_status' => 'required|in:pending,partial,paid,overdue',
            'due_date' => 'nullable|date',
            'reference_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        $validated['farm_owner_id'] = $farmOwner->id;
        $validated['recorded_by'] = Auth::id();

        Expense::create($validated);
        Cache::forget("farm_{$farmOwner->id}_expense_stats");

        return redirect()->route('expenses.index')->with('success', 'Expense recorded.');
    }

    public function show(Expense $expense)
    {
        $farmOwner = $this->getFarmOwner();
        abort_if($expense->farm_owner_id !== $farmOwner->id, 403);

        $expense->load(['supplier', 'recordedBy', 'approvedBy']);

        return view('farmowner.expenses.show', compact('expense'));
    }

    public function edit(Expense $expense)
    {
        $farmOwner = $this->getFarmOwner();
        abort_if($expense->farm_owner_id !== $farmOwner->id, 403);

        $suppliers = Supplier::byFarmOwner($farmOwner->id)->active()->select('id', 'company_name')->get();

        return view('farmowner.expenses.edit', compact('expense', 'suppliers'));
    }

    public function update(Request $request, Expense $expense)
    {
        $farmOwner = $this->getFarmOwner();
        abort_if($expense->farm_owner_id !== $farmOwner->id, 403);

        $validated = $request->validate([
            'supplier_id' => 'nullable|exists:suppliers,id',
            'category' => 'required|in:feeds,vaccines,medications,utilities,labor,equipment,maintenance,transportation,marketing,taxes,insurance,miscellaneous',
            'description' => 'required|string|max:255',
            'amount' => 'nullable|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
            'expense_date' => 'required|date',
            'payment_status' => 'required|in:pending,partial,paid,overdue',
            'reference_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        $expense->update($validated);
        Cache::forget("farm_{$farmOwner->id}_expense_stats");

        return redirect()->route('expenses.show', $expense)->with('success', 'Expense updated.');
    }

    public function destroy(Expense $expense)
    {
        $farmOwner = $this->getFarmOwner();
        abort_if($expense->farm_owner_id !== $farmOwner->id, 403);

        $expense->delete();
        Cache::forget("farm_{$farmOwner->id}_expense_stats");

        return redirect()->route('expenses.index')->with('success', 'Expense deleted.');
    }

    public function markPaid(Request $request, Expense $expense)
    {
        $farmOwner = $this->getFarmOwner();
        abort_if($expense->farm_owner_id !== $farmOwner->id, 403);

        $validated = $request->validate([
            'payment_method' => 'required|in:cash,bank_transfer,check,credit,gcash,maya',
            'payment_reference' => 'nullable|string|max:100',
        ]);

        $expense->markPaid(
            $validated['payment_method'],
            $validated['payment_reference'] ?? null
        );

        Cache::forget("farm_{$farmOwner->id}_expense_stats");

        return redirect()->route('expenses.show', $expense)->with('success', 'Expense marked as paid.');
    }
}
