<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\FarmOwner;
use App\Models\Order;
use App\Models\Subscription;
use App\Models\Product;
use App\Models\Flock;
use App\Models\Vaccination;
use App\Models\SupplyItem;
use App\Models\Supplier;
use App\Models\Driver;
use App\Models\Delivery;
use App\Models\Expense;
use App\Models\IncomeRecord;
use App\Models\SupportTicket;
use App\Models\Employee;
use App\Models\Attendance;
use App\Models\Payroll;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class SuperAdminController extends Controller
{

    public function index()
    {
        try {
            $stats = cache()->remember('admin_dashboard_stats', 300, function () {
                return [
                    'total_farm_owners' => FarmOwner::count(),
                    'pending_verifications' => FarmOwner::where('permit_status', 'pending')->count(),
                    'active_subscriptions' => Subscription::where('status', 'active')->count(),
                    'total_orders' => Order::count(),
                    'total_revenue' => Order::where('payment_status', 'paid')->sum('total_amount') ?? 0,
                    'total_users' => User::count(),
                ];
            });

            $recent_farm_owners = FarmOwner::with('user:id,name,email')
                ->select('id', 'user_id', 'farm_name', 'permit_status', 'created_at')
                ->latest('created_at')
                ->limit(10)
                ->get();

            $pending_farm_owners = FarmOwner::with('user:id,name,email')
                ->select('id', 'user_id', 'farm_name', 'permit_status', 'created_at')
                ->where('permit_status', 'pending')
                ->latest('created_at')
                ->limit(5)
                ->get();

            return view('superadmin.dashboard', compact('stats', 'recent_farm_owners', 'pending_farm_owners'));
        } catch (\Exception $e) {
            Log::error('Dashboard error: ' . $e->getMessage());
            return view('superadmin.dashboard', [
                'stats' => ['total_users' => 0, 'total_farm_owners' => 0, 'pending_verifications' => 0, 'active_subscriptions' => 0, 'total_orders' => 0, 'total_revenue' => 0],
                'recent_farm_owners' => collect([]),
                'pending_farm_owners' => collect([]),
            ]);
        }
    }

    public function farm_owners()
    {
        $farm_owners = FarmOwner::with('user:id,name,email')
            ->select('id', 'user_id', 'farm_name', 'permit_status', 'valid_id_path', 'created_at')
            ->withCount('products', 'orders')
            ->latest('created_at')
            ->paginate(20);

        return view('superadmin.farm-owners', compact('farm_owners'));
    }

    public function show_farm_owner($id)
    {
        $farm_owner = FarmOwner::with(['user:id,name,email', 'products'])
            ->withCount('products', 'orders')
            ->findOrFail($id);

        $products = $farm_owner->products()
            ->select('id', 'farm_owner_id', 'name', 'sku', 'category', 'price', 'quantity_available', 'quantity_sold', 'status')
            ->orderBy('quantity_available', 'asc')
            ->get();

        $total_sales = $farm_owner->orders()->where('payment_status', 'paid')->sum('total_amount');

        return view('superadmin.farm-owner-show', compact('farm_owner', 'products', 'total_sales'));
    }

    public function approve_farm_owner($id)
    {
        $farm_owner = FarmOwner::with('user:id,name,email')->findOrFail($id);
        
        if ($farm_owner->permit_status !== 'pending') {
            return redirect()->back()->with('error', 'Farm owner is not pending approval');
        }

        $farm_owner->update(['permit_status' => 'approved']);
        Cache::forget("farm_{$farm_owner->id}_stats");

        $mailDeliveryFailed = false;

        try {
            if ($farm_owner->user?->email) {
                Mail::raw(
                    "Good news! Your farm owner registration for {$farm_owner->farm_name} has been approved by the Super Admin. You can now log in and continue setup.",
                    function ($message) use ($farm_owner): void {
                        $message->to($farm_owner->user->email, $farm_owner->user->name ?? $farm_owner->farm_name)
                            ->subject('Farm Owner Registration Approved');
                    }
                );
            }
        } catch (\Throwable $e) {
            $mailDeliveryFailed = true;
            Log::error('Failed to send farm owner approval email', [
                'farm_owner_id' => $farm_owner->id,
                'email' => $farm_owner->user?->email,
                'error' => $e->getMessage(),
            ]);
        }
        
        Log::info('Farm owner approved', ['farm_owner_id' => $id, 'user_id' => $farm_owner->user_id]);

        if ($mailDeliveryFailed) {
            return redirect()->back()->with('success', 'Farm owner approved successfully, but notification email could not be delivered.');
        }

        return redirect()->back()->with('success', 'Farm owner approved successfully and notification email sent.');
    }

    public function reject_farm_owner($id, Request $request)
    {
        $request->validate(['reason' => 'required|string|max:500']);

        $farm_owner = FarmOwner::with('user:id,name,email')->findOrFail($id);
        $farm_owner->update(['permit_status' => 'rejected']);
        Cache::forget("farm_{$farm_owner->id}_stats");

        $mailDeliveryFailed = false;

        try {
            if ($farm_owner->user?->email) {
                Mail::raw(
                    "Your farm owner registration for {$farm_owner->farm_name} has been denied by the Super Admin. Reason: {$request->reason}",
                    function ($message) use ($farm_owner): void {
                        $message->to($farm_owner->user->email, $farm_owner->user->name ?? $farm_owner->farm_name)
                            ->subject('Farm Owner Registration Update');
                    }
                );
            }
        } catch (\Throwable $e) {
            $mailDeliveryFailed = true;
            Log::error('Failed to send farm owner rejection email', [
                'farm_owner_id' => $farm_owner->id,
                'email' => $farm_owner->user?->email,
                'error' => $e->getMessage(),
            ]);
        }
        
        Log::info('Farm owner rejected', ['farm_owner_id' => $id, 'reason' => $request->reason]);

        if ($mailDeliveryFailed) {
            return redirect()->back()->with('success', 'Farm owner rejected, but notification email could not be delivered.');
        }

        return redirect()->back()->with('success', 'Farm owner rejected and notification email sent.');
    }

    public function orders()
    {
        $orders = Order::with([
            'consumer:id,name,email',
            'farmOwner:id,farm_name,user_id'
        ])
        ->select('id', 'consumer_id', 'farm_owner_id', 'total_amount', 'status', 'payment_status', 'created_at')
        ->latest('created_at')
        ->paginate(20);

        $stats = cache()->remember('admin_orders_stats', 300, function () {
            return [
                'total_orders' => Order::count(),
                'pending_orders' => Order::where('status', 'pending')->count(),
                'paid_orders' => Order::where('payment_status', 'paid')->count(),
                'total_revenue' => Order::where('payment_status', 'paid')->sum('total_amount') ?? 0,
            ];
        });

        // Sales per farm owner
        $sales_per_farm = Order::select('farm_owner_id', DB::raw('COUNT(*) as order_count'), DB::raw('SUM(total_amount) as total_sales'), DB::raw('SUM(CASE WHEN payment_status = \'paid\' THEN total_amount ELSE 0 END) as paid_sales'))
            ->with('farmOwner:id,farm_name')
            ->groupBy('farm_owner_id')
            ->orderByDesc('total_sales')
            ->get();

        return view('superadmin.orders', compact('orders', 'stats', 'sales_per_farm'));
    }

    public function monitoring()
    {
        $stats = [
            'flocks_total' => Flock::count(),
            'vaccinations_overdue' => Vaccination::overdue()->count(),
            'supplies_low_stock' => SupplyItem::lowStock()->count(),
            'suppliers_total' => Supplier::count(),
            'drivers_active' => Driver::whereIn('status', ['available', 'on_delivery'])->count(),
            'deliveries_pending' => Delivery::pending()->count(),
            'expenses_this_month' => (float) (Expense::thisMonth()->sum('total_amount') ?? 0),
            'income_this_month' => (float) (IncomeRecord::thisMonth()->sum('total_amount') ?? 0),
            'support_open' => SupportTicket::where('status', 'open')->count(),
            'employees_active' => Employee::active()->count(),
            'attendance_absent_today' => Attendance::today()->absent()->count(),
            'payroll_pending' => Payroll::pending()->count(),
        ];

        $flocks = Flock::with(['farmOwner:id,farm_name,user_id', 'farmOwner.user:id,name'])
            ->select('id', 'farm_owner_id', 'batch_name', 'current_count', 'mortality_count', 'status', 'updated_at')
            ->latest('updated_at')
            ->limit(8)
            ->get();

        $vaccinations = Vaccination::with(['farmOwner:id,farm_name', 'flock:id,batch_name'])
            ->select('id', 'farm_owner_id', 'flock_id', 'name', 'next_due_date', 'status')
            ->orderBy('next_due_date')
            ->limit(8)
            ->get();

        $supplies = SupplyItem::with(['farmOwner:id,farm_name', 'supplier:id,company_name'])
            ->select('id', 'farm_owner_id', 'supplier_id', 'name', 'quantity_on_hand', 'reorder_point', 'status')
            ->orderByRaw('quantity_on_hand <= reorder_point desc')
            ->limit(8)
            ->get();

        $suppliers = Supplier::with('farmOwner:id,farm_name')
            ->select('id', 'farm_owner_id', 'company_name', 'category', 'status', 'outstanding_balance')
            ->orderByDesc('updated_at')
            ->limit(8)
            ->get();

        $drivers = Driver::with('farmOwner:id,farm_name')
            ->select('id', 'farm_owner_id', 'name', 'phone', 'vehicle_type', 'vehicle_plate', 'status', 'rating')
            ->orderByDesc('updated_at')
            ->limit(8)
            ->get();

        $deliveries = Delivery::with(['farmOwner:id,farm_name', 'driver:id,name', 'order:id,consumer_id,order_number', 'order.consumer:id,name'])
            ->select('id', 'farm_owner_id', 'driver_id', 'order_id', 'tracking_number', 'status', 'scheduled_date', 'delivered_at')
            ->latest('created_at')
            ->limit(8)
            ->get();

        $expenses = Expense::with('farmOwner:id,farm_name')
            ->select('id', 'farm_owner_id', 'expense_number', 'category', 'total_amount', 'payment_status', 'expense_date')
            ->latest('expense_date')
            ->limit(8)
            ->get();

        $incomeRecords = IncomeRecord::with('farmOwner:id,farm_name')
            ->select('id', 'farm_owner_id', 'income_number', 'category', 'total_amount', 'payment_status', 'income_date')
            ->latest('income_date')
            ->limit(8)
            ->get();

        $supportTickets = SupportTicket::with(['farmOwner:id,farm_name,user_id', 'farmOwner.user:id,name', 'latestMessage:id,support_ticket_id,sender_role,sender_id,created_at', 'latestMessage.sender:id,name'])
            ->select('id', 'farm_owner_id', 'subject', 'status', 'last_message_at', 'created_at')
            ->latest('last_message_at')
            ->limit(8)
            ->get();

        $employees = Employee::with('farmOwner:id,farm_name')
            ->select('id', 'farm_owner_id', 'first_name', 'last_name', 'department', 'position', 'status')
            ->latest('updated_at')
            ->limit(8)
            ->get();

        $attendance = Attendance::with(['farmOwner:id,farm_name', 'employee:id,first_name,last_name'])
            ->select('id', 'farm_owner_id', 'employee_id', 'work_date', 'status', 'hours_worked', 'late_minutes')
            ->latest('work_date')
            ->limit(8)
            ->get();

        $payroll = Payroll::with(['farmOwner:id,farm_name', 'employee:id,first_name,last_name'])
            ->select('id', 'farm_owner_id', 'employee_id', 'payroll_period', 'period_start', 'period_end', 'net_pay', 'status')
            ->latest('created_at')
            ->limit(8)
            ->get();

        return view('superadmin.monitoring', compact(
            'stats',
            'flocks',
            'vaccinations',
            'supplies',
            'suppliers',
            'drivers',
            'deliveries',
            'expenses',
            'incomeRecords',
            'supportTickets',
            'employees',
            'attendance',
            'payroll'
        ));
    }

    public function subscriptions()
    {
        $subscriptions = Subscription::with([
            'farmOwner:id,farm_name,user_id',
            'farmOwner.user:id,name,email'
        ])
        ->select('id', 'farm_owner_id', 'plan_type', 'status', 'started_at', 'ends_at', 'paymongo_subscription_id')
        ->latest('created_at')
        ->paginate(20);

        return view('superadmin.subscriptions', compact('subscriptions'));
    }

    public function users()
    {
        $farmOwnerUsers = User::with([
                'farmOwner:id,user_id,farm_name,permit_status,subscription_status',
                'farmOwner.subscription:id,farm_owner_id,plan_type,status,ends_at',
            ])
            ->where('role', 'farm_owner')
            ->select('id', 'name', 'email', 'role', 'status', 'email_verified_at', 'created_at')
            ->latest('created_at')
            ->paginate(15, ['*'], 'farm_owner_page');

        $departmentUsers = User::query()
            ->whereIn('role', User::DEPARTMENT_ROLES)
            ->select('id', 'name', 'email', 'role', 'status', 'email_verified_at', 'created_at')
            ->latest('created_at')
            ->paginate(15, ['*'], 'department_page');

        $otherUsers = User::query()
            ->where('role', '!=', 'farm_owner')
            ->whereNotIn('role', User::DEPARTMENT_ROLES)
            ->select('id', 'name', 'email', 'role', 'status', 'email_verified_at', 'created_at')
            ->latest('created_at')
            ->paginate(15, ['*'], 'other_page');

        return view('superadmin.users', compact('farmOwnerUsers', 'departmentUsers', 'otherUsers'));
    }
}