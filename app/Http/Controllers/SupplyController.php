<?php

namespace App\Http\Controllers;

use App\Models\SupplyItem;
use App\Models\StockTransaction;
use App\Models\Supplier;
use App\Models\FarmOwner;
use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class SupplyController extends Controller
{
    use \App\Http\Controllers\Concerns\ResolvesFarmOwner;

    public function index(Request $request)
    {
        $farmOwner = $this->getFarmOwner();
        
        $query = SupplyItem::byFarmOwner($farmOwner->id)
            ->with('supplier:id,company_name')
            ->select('id', 'supplier_id', 'name', 'category', 'unit', 'quantity_on_hand', 'minimum_stock', 'unit_cost', 'expiration_date', 'status');

        if ($request->filled('category')) {
            $query->byCategory($request->category);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $items = $query->orderBy('name')->paginate(20);

        $stats = Cache::remember("farm_{$farmOwner->id}_supply_stats", 300, function () use ($farmOwner) {
            return [
                'total_items' => SupplyItem::byFarmOwner($farmOwner->id)->count(),
                'low_stock' => SupplyItem::byFarmOwner($farmOwner->id)->lowStock()->count(),
                'out_of_stock' => SupplyItem::byFarmOwner($farmOwner->id)->outOfStock()->count(),
                'expiring_soon' => SupplyItem::byFarmOwner($farmOwner->id)->expiringSoon(30)->count(),
                'total_value' => SupplyItem::byFarmOwner($farmOwner->id)->selectRaw('SUM(quantity_on_hand * unit_cost) as value')->value('value') ?? 0,
            ];
        });

        return view('farmowner.supplies.index', compact('items', 'stats'));
    }

    public function create()
    {
        $farmOwner = $this->getFarmOwner();
        $suppliers = Supplier::byFarmOwner($farmOwner->id)->active()->select('id', 'company_name')->get();

        return view('farmowner.supplies.create', compact('suppliers'));
    }

    public function store(Request $request)
    {
        $farmOwner = $this->getFarmOwner();

        $validated = $request->validate([
            'supplier_id' => 'nullable|exists:suppliers,id',
            'sku' => 'nullable|string|max:50',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|in:feeds,vitamins,vaccines,medications,equipment,supplements,cleaning,packaging,other',
            'brand' => 'nullable|string|max:100',
            'unit' => 'required|string|max:50',
            'quantity_on_hand' => 'required|numeric|min:0',
            'minimum_stock' => 'nullable|numeric|min:0',
            'reorder_point' => 'nullable|numeric|min:0',
            'unit_cost' => 'required|numeric|min:0',
            'selling_price' => 'nullable|numeric|min:0',
            'expiration_date' => 'nullable|date',
            'batch_number' => 'nullable|string|max:100',
            'storage_location' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $validated['farm_owner_id'] = $farmOwner->id;

        $item = SupplyItem::create($validated);
        $item->updateStatus();
        $item->save();

        Cache::forget("farm_{$farmOwner->id}_supply_stats");

        // Record initial stock transaction
        if ($validated['quantity_on_hand'] > 0) {
            $transaction = StockTransaction::create([
                'farm_owner_id' => $farmOwner->id,
                'supply_item_id' => $item->id,
                'supplier_id' => $validated['supplier_id'],
                'recorded_by' => Auth::id(),
                'transaction_type' => 'stock_in',
                'quantity' => $validated['quantity_on_hand'],
                'unit_cost' => $validated['unit_cost'],
                'total_cost' => $validated['quantity_on_hand'] * $validated['unit_cost'],
                'quantity_before' => 0,
                'quantity_after' => $validated['quantity_on_hand'],
                'transaction_date' => today(),
                'reason' => 'Initial stock entry',
            ]);

            $this->createExpenseFromStockInTransaction($farmOwner->id, $transaction, $item, Auth::id());
        }

        return redirect()->route('supplies.index')->with('success', 'Supply item added.');
    }

    public function show(SupplyItem $supply)
    {
        $farmOwner = $this->getFarmOwner();
        abort_if($supply->farm_owner_id !== $farmOwner->id, 403);

        $supply->load(['supplier', 'stockTransactions' => fn($q) => $q->latest('transaction_date')->limit(20)]);

        return view('farmowner.supplies.show', compact('supply'));
    }

    public function edit(SupplyItem $supply)
    {
        $farmOwner = $this->getFarmOwner();
        abort_if($supply->farm_owner_id !== $farmOwner->id, 403);

        $suppliers = Supplier::byFarmOwner($farmOwner->id)->active()->select('id', 'company_name')->get();

        return view('farmowner.supplies.edit', compact('supply', 'suppliers'));
    }

    public function update(Request $request, SupplyItem $supply)
    {
        $farmOwner = $this->getFarmOwner();
        abort_if($supply->farm_owner_id !== $farmOwner->id, 403);

        $validated = $request->validate([
            'supplier_id' => 'nullable|exists:suppliers,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|in:feeds,vitamins,vaccines,medications,equipment,supplements,cleaning,packaging,other',
            'brand' => 'nullable|string|max:100',
            'unit' => 'required|string|max:50',
            'minimum_stock' => 'nullable|numeric|min:0',
            'reorder_point' => 'nullable|numeric|min:0',
            'unit_cost' => 'required|numeric|min:0',
            'selling_price' => 'nullable|numeric|min:0',
            'expiration_date' => 'nullable|date',
            'storage_location' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $supply->update($validated);
        $supply->updateStatus();
        $supply->save();

        Cache::forget("farm_{$farmOwner->id}_supply_stats");

        return redirect()->route('supplies.show', $supply)->with('success', 'Supply item updated.');
    }

    public function destroy(SupplyItem $supply)
    {
        $farmOwner = $this->getFarmOwner();
        abort_if($supply->farm_owner_id !== $farmOwner->id, 403);

        $supply->delete();
        Cache::forget("farm_{$farmOwner->id}_supply_stats");

        return redirect()->route('supplies.index')->with('success', 'Supply item removed.');
    }

    // Stock In
    public function stockIn(Request $request, SupplyItem $supply)
    {
        $farmOwner = $this->getFarmOwner();
        abort_if($supply->farm_owner_id !== $farmOwner->id, 403);

        $validated = $request->validate([
            'quantity' => 'required|numeric|min:0.01',
            'unit_cost' => 'nullable|numeric|min:0',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'invoice_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        $transaction = $supply->adjustStock($validated['quantity'], 'stock_in', Auth::id(), $validated['notes'] ?? null);

        $effectiveUnitCost = array_key_exists('unit_cost', $validated) && $validated['unit_cost'] !== null
            ? (float) $validated['unit_cost']
            : (float) $supply->unit_cost;
        $totalCost = (float) $validated['quantity'] * $effectiveUnitCost;

        $transaction->update([
            'supplier_id' => $validated['supplier_id'] ?? $supply->supplier_id,
            'unit_cost' => $effectiveUnitCost,
            'total_cost' => $totalCost,
            'invoice_number' => $validated['invoice_number'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        if (array_key_exists('unit_cost', $validated) && $validated['unit_cost'] !== null) {
            $supply->update(['unit_cost' => $effectiveUnitCost]);
        }

        $this->createExpenseFromStockInTransaction($farmOwner->id, $transaction, $supply, Auth::id());
        
        Cache::forget("farm_{$farmOwner->id}_supply_stats");

        return redirect()->route('supplies.show', $supply)->with('success', 'Stock added successfully.');
    }

    // Stock Out
    public function stockOut(Request $request, SupplyItem $supply)
    {
        $farmOwner = $this->getFarmOwner();
        abort_if($supply->farm_owner_id !== $farmOwner->id, 403);

        $validated = $request->validate([
            'quantity' => 'required|numeric|min:0.01|max:' . $supply->quantity_on_hand,
            'reason' => 'required|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $supply->adjustStock($validated['quantity'], 'stock_out', Auth::id(), $validated['reason']);
        
        Cache::forget("farm_{$farmOwner->id}_supply_stats");

        return redirect()->route('supplies.show', $supply)->with('success', 'Stock deducted successfully.');
    }

    // Low Stock Alerts
    public function lowStock()
    {
        $farmOwner = $this->getFarmOwner();

        $lowStock = SupplyItem::byFarmOwner($farmOwner->id)
            ->lowStock()
            ->with('supplier:id,company_name')
            ->orderBy('quantity_on_hand')
            ->get();

        $expiring = SupplyItem::byFarmOwner($farmOwner->id)
            ->expiringSoon(30)
            ->orderBy('expiration_date')
            ->get();

        return view('farmowner.supplies.alerts', compact('lowStock', 'expiring'));
    }

    private function createExpenseFromStockInTransaction(int $farmOwnerId, StockTransaction $transaction, SupplyItem $item, int $recordedBy): void
    {
        if ($transaction->transaction_type !== 'stock_in' || (float) $transaction->total_cost <= 0) {
            return;
        }

        Expense::updateOrCreate(
            [
                'source_type' => 'stock_transaction',
                'source_id' => $transaction->id,
            ],
            [
                'farm_owner_id' => $farmOwnerId,
                'recorded_by' => $recordedBy,
                'supplier_id' => $transaction->supplier_id,
                'category' => $this->mapSupplyCategoryToExpenseCategory((string) $item->category),
                'description' => 'Supply stock-in: ' . $item->name,
                'amount' => $transaction->total_cost,
                'tax_amount' => 0,
                'total_amount' => $transaction->total_cost,
                'expense_date' => $transaction->transaction_date ?? today(),
                'payment_status' => 'pending',
                'payment_method' => null,
                'reference_number' => $transaction->invoice_number ?: $transaction->reference_number,
                'status' => 'approved',
                'is_auto_generated' => true,
                'notes' => 'Auto-generated from stock-in transaction.',
            ]
        );

        Cache::forget("farm_{$farmOwnerId}_expense_stats");
    }

    private function mapSupplyCategoryToExpenseCategory(string $supplyCategory): string
    {
        return match ($supplyCategory) {
            'feeds' => 'feeds',
            'vaccines' => 'vaccines',
            'medications', 'vitamins', 'supplements' => 'medications',
            'equipment' => 'equipment',
            'cleaning' => 'maintenance',
            default => 'miscellaneous',
        };
    }
}
