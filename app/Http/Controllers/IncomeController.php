<?php

namespace App\Http\Controllers;

use App\Models\IncomeRecord;
use App\Models\Order;
use App\Models\FarmOwner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class IncomeController extends Controller
{
    use \App\Http\Controllers\Concerns\ResolvesFarmOwner;

    public function index(Request $request)
    {
        $farmOwner = $this->getFarmOwner();
        $selectedSource = $request->input('source', $request->input('category'));
        
        $query = IncomeRecord::byFarmOwner($farmOwner->id)
            ->with('recordedBy:id,name')
            ->select('id', 'income_number', 'order_id', 'category', 'description', 'amount', 'income_date', 'payment_method', 'recorded_by');

        if (!empty($selectedSource)) {
            $query->where('category', $selectedSource);
        }

        if ($request->filled('month')) {
            $date = Carbon::parse($request->month);
            $monthStart = $date->copy()->startOfMonth()->toDateString();
            $nextMonthStart = $date->copy()->startOfMonth()->addMonth()->toDateString();

            $query->where('income_date', '>=', $monthStart)
                ->where('income_date', '<', $nextMonthStart);
        }

        $incomes = $query->latest('income_date')->paginate(20);

        $stats = Cache::remember("farm_{$farmOwner->id}_income_stats", 300, function () use ($farmOwner) {
            $thisMonthStart = now()->startOfMonth()->toDateString();
            $nextMonthStart = now()->startOfMonth()->addMonth()->toDateString();

            return [
                'total_this_month' => IncomeRecord::byFarmOwner($farmOwner->id)
                    ->where('income_date', '>=', $thisMonthStart)
                    ->where('income_date', '<', $nextMonthStart)
                    ->sum('amount'),
                'by_source' => IncomeRecord::byFarmOwner($farmOwner->id)
                    ->where('income_date', '>=', $thisMonthStart)
                    ->where('income_date', '<', $nextMonthStart)
                    ->selectRaw('category, SUM(amount) as total')
                    ->groupBy('category')
                    ->pluck('total', 'category'),
            ];
        });

        return view('farmowner.income.index', compact('incomes', 'stats'));
    }

    public function create()
    {
        return view('farmowner.income.create');
    }

    public function store(Request $request)
    {
        $farmOwner = $this->getFarmOwner();

        $validated = $request->validate([
            'order_id' => 'nullable|exists:orders,id',
            'category' => 'required|in:product_sales,egg_sales,chicken_sales,chick_sales,feed_sales,service_income,other',
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'income_date' => 'required|date',
            'payment_method' => 'nullable|in:cash,bank_transfer,check,gcash,maya,credit',
            'reference_number' => 'nullable|string|max:100',
            'customer_name' => 'nullable|string|max:255',
            'customer_contact' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        $validated['farm_owner_id'] = $farmOwner->id;
        $validated['recorded_by'] = Auth::id();

        IncomeRecord::create($validated);
        Cache::forget("farm_{$farmOwner->id}_income_stats");

        return redirect()->route('income.index')->with('success', 'Income recorded.');
    }

    public function show(IncomeRecord $income)
    {
        $farmOwner = $this->getFarmOwner();
        abort_if($income->farm_owner_id !== $farmOwner->id, 403);

        $income->load(['order', 'recordedBy']);

        return view('farmowner.income.show', compact('income'));
    }

    public function edit(IncomeRecord $income)
    {
        $farmOwner = $this->getFarmOwner();
        abort_if($income->farm_owner_id !== $farmOwner->id, 403);

        return view('farmowner.income.edit', compact('income'));
    }

    public function update(Request $request, IncomeRecord $income)
    {
        $farmOwner = $this->getFarmOwner();
        abort_if($income->farm_owner_id !== $farmOwner->id, 403);

        $validated = $request->validate([
            'category' => 'required|in:product_sales,egg_sales,chicken_sales,chick_sales,feed_sales,service_income,other',
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'income_date' => 'required|date',
            'payment_method' => 'nullable|in:cash,bank_transfer,check,gcash,maya,credit',
            'customer_name' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $income->update($validated);
        Cache::forget("farm_{$farmOwner->id}_income_stats");

        return redirect()->route('income.show', $income)->with('success', 'Income updated.');
    }

    public function destroy(IncomeRecord $income)
    {
        $farmOwner = $this->getFarmOwner();
        abort_if($income->farm_owner_id !== $farmOwner->id, 403);

        $income->delete();
        Cache::forget("farm_{$farmOwner->id}_income_stats");

        return redirect()->route('income.index')->with('success', 'Income deleted.');
    }
}
