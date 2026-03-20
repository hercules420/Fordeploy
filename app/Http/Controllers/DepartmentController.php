<?php

namespace App\Http\Controllers;

use App\Models\Delivery;
use App\Models\Driver;
use App\Models\Employee;
use App\Models\Expense;
use App\Models\Flock;
use App\Models\IncomeRecord;
use App\Models\Order;
use App\Models\Supplier;
use App\Models\Vaccination;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class DepartmentController extends Controller
{
    /**
     * Resolve the farm owner ID from the logged-in employee's record.
     */
    private function getFarmOwnerId(): ?int
    {
        $employee = Employee::where('user_id', Auth::id())->first();
        return $employee?->farm_owner_id;
    }

    // ----------------------------------------------------------------
    // Logistics Dashboard
    // ----------------------------------------------------------------
    public function logistics()
    {
        $farmOwnerId = $this->getFarmOwnerId();

        $stats = Cache::remember("dept_logistics_{$farmOwnerId}", 120, function () use ($farmOwnerId) {
            $base = Delivery::where('farm_owner_id', $farmOwnerId);
            return [
                'total'      => (clone $base)->count(),
                'pending'    => (clone $base)->whereIn('status', ['pending', 'assigned'])->count(),
                'in_transit' => (clone $base)->whereIn('status', ['dispatched', 'in_transit'])->count(),
                'delivered'  => (clone $base)->where('status', 'delivered')->whereDate('delivered_at', today())->count(),
                'drivers'    => Driver::where('farm_owner_id', $farmOwnerId)->where('status', 'active')->count(),
            ];
        });

        $recentDeliveries = Delivery::where('farm_owner_id', $farmOwnerId)
            ->with(['driver:id,first_name,last_name', 'order:id,order_number'])
            ->orderByDesc('created_at')
            ->limit(8)
            ->get();

        return view('department.logistics', compact('stats', 'recentDeliveries'));
    }

    // ----------------------------------------------------------------
    // Farm Operations Dashboard
    // ----------------------------------------------------------------
    public function farmOperations()
    {
        $farmOwnerId = $this->getFarmOwnerId();

        $stats = Cache::remember("dept_farm_ops_{$farmOwnerId}", 120, function () use ($farmOwnerId) {
            return [
                'active_flocks'    => Flock::where('farm_owner_id', $farmOwnerId)->where('status', 'active')->count(),
                'total_birds'      => Flock::where('farm_owner_id', $farmOwnerId)->where('status', 'active')->sum('current_count'),
                'upcoming_vaccinations' => Vaccination::where('farm_owner_id', $farmOwnerId)
                    ->where('scheduled_date', '>=', today())
                    ->where('scheduled_date', '<=', today()->addDays(7))
                    ->where('status', 'scheduled')
                    ->count(),
            ];
        });

        $recentFlocks = Flock::where('farm_owner_id', $farmOwnerId)
            ->orderByDesc('created_at')
            ->limit(8)
            ->get(['id', 'batch_name', 'breed_type', 'current_count', 'status', 'created_at']);

        return view('department.farm_operations', compact('stats', 'recentFlocks'));
    }

    // ----------------------------------------------------------------
    // Finance Dashboard
    // ----------------------------------------------------------------
    public function finance()
    {
        $farmOwnerId = $this->getFarmOwnerId();

        $stats = Cache::remember("dept_finance_{$farmOwnerId}", 120, function () use ($farmOwnerId) {
            return [
                'total_expenses'  => Expense::where('farm_owner_id', $farmOwnerId)->sum('amount'),
                'pending_expenses'=> Expense::where('farm_owner_id', $farmOwnerId)->whereIn('payment_status', ['pending', 'overdue'])->count(),
                'total_income'    => IncomeRecord::where('farm_owner_id', $farmOwnerId)->sum('amount'),
                'pending_income'  => IncomeRecord::where('farm_owner_id', $farmOwnerId)->where('payment_status', 'pending')->count(),
            ];
        });

        $recentExpenses = Expense::where('farm_owner_id', $farmOwnerId)
            ->orderByDesc('expense_date')
            ->limit(6)
            ->get(['id', 'expense_number', 'category', 'amount', 'payment_status', 'expense_date']);

        return view('department.finance', compact('stats', 'recentExpenses'));
    }

    // ----------------------------------------------------------------
    // Sales Dashboard
    // ----------------------------------------------------------------
    public function sales()
    {
        $farmOwnerId = $this->getFarmOwnerId();

        $stats = Cache::remember("dept_sales_{$farmOwnerId}", 120, function () use ($farmOwnerId) {
            $base = Order::where('farm_owner_id', $farmOwnerId);
            return [
                'total_orders'   => (clone $base)->count(),
                'pending_orders' => (clone $base)->where('status', 'pending')->count(),
                'confirmed'      => (clone $base)->where('status', 'confirmed')->count(),
                'delivered'      => (clone $base)->where('status', 'delivered')->count(),
                'revenue'        => (clone $base)->where('payment_status', 'paid')->sum('total_amount'),
            ];
        });

        $recentOrders = Order::where('farm_owner_id', $farmOwnerId)
            ->with('consumer:id,name')
            ->orderByDesc('created_at')
            ->limit(8)
            ->get(['id', 'order_number', 'consumer_id', 'total_amount', 'status', 'payment_status', 'created_at']);

        return view('department.sales', compact('stats', 'recentOrders'));
    }

    // ----------------------------------------------------------------
    // Admin Dashboard
    // ----------------------------------------------------------------
    public function admin()
    {
        $farmOwnerId = $this->getFarmOwnerId();

        $stats = Cache::remember("dept_admin_{$farmOwnerId}", 120, function () use ($farmOwnerId) {
            return [
                'total_employees' => Employee::where('farm_owner_id', $farmOwnerId)->count(),
                'active_employees'=> Employee::where('farm_owner_id', $farmOwnerId)->where('status', 'active')->count(),
                'total_suppliers' => Supplier::where('farm_owner_id', $farmOwnerId)->count(),
            ];
        });

        $recentEmployees = Employee::where('farm_owner_id', $farmOwnerId)
            ->orderByDesc('created_at')
            ->limit(8)
            ->get(['id', 'employee_id', 'first_name', 'last_name', 'department', 'position', 'status', 'created_at']);

        return view('department.admin', compact('stats', 'recentEmployees'));
    }
}
