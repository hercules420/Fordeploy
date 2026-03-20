<?php

namespace App\Http\Controllers;

use App\Models\Payroll;
use App\Models\Employee;
use App\Models\Attendance;
use App\Models\PayrollEditRequest;
use App\Models\InternalMessage;
use App\Models\FarmOwner;
use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PayrollController extends Controller
{
    use \App\Http\Controllers\Concerns\ResolvesFarmOwner;

    private const MAX_OVERTIME_MULTIPLIER = 2.00;

    public function index(Request $request)
    {
        $farmOwner = $this->getFarmOwner();
        
        $query = Payroll::byFarmOwner($farmOwner->id)
            ->with('employee:id,first_name,last_name,position')
            ->select('id', 'employee_id', 'payroll_period', 'period_start', 'period_end', 'basic_pay', 'net_pay', 'status', 'pay_date');

        if ($request->filled('status')) {
            $statusFilter = (string) $request->status;
            $query->where(function ($q) use ($statusFilter) {
                $q->where('status', $statusFilter)
                    ->orWhere('workflow_status', $statusFilter);
            });
        }

        if ($request->filled('month')) {
            $date = Carbon::parse($request->month);
            $monthStart = $date->copy()->startOfMonth()->toDateString();
            $nextMonthStart = $date->copy()->startOfMonth()->addMonth()->toDateString();

            $query->where('period_start', '>=', $monthStart)
                ->where('period_start', '<', $nextMonthStart);
        }

        $payrolls = $query->latest('period_start')->paginate(20);

        $currentMonthStart = now()->startOfMonth()->toDateString();
        $nextMonthStart = now()->startOfMonth()->addMonth()->toDateString();

        $statsAggregate = Payroll::byFarmOwner($farmOwner->id)
            ->selectRaw("COALESCE(SUM(CASE WHEN status IN ('draft', 'pending') THEN net_pay ELSE 0 END), 0) as pending")
            ->selectRaw("COALESCE(SUM(CASE WHEN workflow_status = 'pending_finance' THEN net_pay ELSE 0 END), 0) as pending_finance")
            ->selectRaw(
                "COALESCE(SUM(CASE WHEN status = 'paid' AND pay_date >= ? AND pay_date < ? THEN net_pay ELSE 0 END), 0) as paid_this_month",
                [$currentMonthStart, $nextMonthStart]
            )
            ->first();

        $stats = [
            'pending' => (float) ($statsAggregate->pending ?? 0),
            'pending_finance' => (float) ($statsAggregate->pending_finance ?? 0),
            'paid_this_month' => (float) ($statsAggregate->paid_this_month ?? 0),
        ];

        return view('farmowner.payroll.index', compact('payrolls', 'stats'));
    }

    public function create()
    {
        $farmOwner = $this->getFarmOwner();
        $employees = Employee::byFarmOwner($farmOwner->id)
            ->active()
            ->select('id', 'first_name', 'last_name', 'position', 'daily_rate', 'monthly_salary', 'performance_rating')
            ->get();

        return view('farmowner.payroll.create', compact('employees'));
    }

    public function store(Request $request)
    {
        $farmOwner = $this->getFarmOwner();

        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after:period_start',
            'days_worked' => 'required|numeric|min:0',
            'basic_pay' => 'required|numeric|min:0',
            'overtime_hours' => 'nullable|numeric|min:0',
            'overtime_pay' => 'nullable|numeric|min:0',
            'holiday_pay' => 'nullable|numeric|min:0',
            'allowances' => 'nullable|numeric|min:0',
            'bonuses' => 'nullable|numeric|min:0',
            'sss_deduction' => 'nullable|numeric|min:0',
            'philhealth_deduction' => 'nullable|numeric|min:0',
            'pagibig_deduction' => 'nullable|numeric|min:0',
            'tax_deduction' => 'nullable|numeric|min:0',
            'loan_deduction' => 'nullable|numeric|min:0',
            'insurance_deduction' => 'nullable|numeric|min:0',
            'reimbursement_deduction' => 'nullable|numeric|min:0',
            'other_deductions' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $validated['farm_owner_id'] = $farmOwner->id;
        $validated['processed_by'] = Auth::id();

        // Generate payroll period label
        $count = Payroll::byFarmOwner($farmOwner->id)->whereYear('created_at', now()->year)->count() + 1;
        $validated['payroll_period'] = 'PAY-' . now()->format('Y') . '-' . str_pad($count, 5, '0', STR_PAD_LEFT);
        $validated['pay_date'] = Carbon::parse((string) $validated['period_end'])->toDateString();

        [$hoursWorked, $regularHours, $overtimeHours, $lateMinutes, $lateDeduction, $hourlyRate] =
            $this->buildAttendancePayrollMetrics(
                (int) $validated['employee_id'],
                Carbon::parse((string) $validated['period_start']),
                Carbon::parse((string) $validated['period_end']),
                (float) ($validated['days_worked'] ?? 0),
                (float) ($validated['basic_pay'] ?? 0)
            );

        $employee = Employee::select('id', 'performance_rating')->find((int) $validated['employee_id']);

        $validated['hours_worked'] = $hoursWorked;
        $validated['regular_hours'] = $regularHours;
        $validated['overtime_hours'] = max((float) ($validated['overtime_hours'] ?? 0), $overtimeHours);
        $validated['hourly_rate'] = $hourlyRate;
        $validated['late_deduction'] = $lateDeduction;

        [$rating, $bonusPerBlock, $otBlocks, $otMultiplier] =
            $this->buildOvertimeRateInfo($employee, (float) $validated['overtime_hours']);

        if (!isset($validated['overtime_pay']) || (float) $validated['overtime_pay'] <= 0) {
            $validated['overtime_pay'] = round($validated['overtime_hours'] * $hourlyRate * $otMultiplier, 2);
        }

        $policyNote = sprintf(
            'OT Policy -> rating:%d, completed_30m_blocks:%d, bonus_per_30m:%.3f, ot_multiplier:%.3f, cap:%.2f',
            $rating,
            $otBlocks,
            $bonusPerBlock,
            $otMultiplier,
            self::MAX_OVERTIME_MULTIPLIER
        );

        $validated['notes'] = trim(($validated['notes'] ?? '') . (($validated['notes'] ?? '') ? "\n" : '') . $policyNote);

        // Calculate totals
        $grossPay = ($validated['basic_pay'] ?? 0) 
            + ($validated['overtime_pay'] ?? 0) 
            + ($validated['holiday_pay'] ?? 0) 
            + ($validated['allowances'] ?? 0) 
            + ($validated['bonuses'] ?? 0);

        $totalDeductions = ($validated['sss_deduction'] ?? 0)
            + ($validated['philhealth_deduction'] ?? 0)
            + ($validated['pagibig_deduction'] ?? 0)
            + ($validated['tax_deduction'] ?? 0)
            + ($validated['late_deduction'] ?? 0)
            + ($validated['loan_deduction'] ?? 0)
            + ($validated['insurance_deduction'] ?? 0)
            + ($validated['reimbursement_deduction'] ?? 0)
            + ($validated['other_deductions'] ?? 0);

        $validated['gross_pay'] = $grossPay;
        $validated['total_deductions'] = $totalDeductions;
        $validated['net_pay'] = $grossPay - $totalDeductions;
        $validated['workflow_status'] = Auth::user()?->isHR() ? 'pending_finance' : 'finance_approved';
        $validated['status'] = 'pending';

        $payroll = Payroll::create($validated);

        if (Auth::user()?->isHR()) {
            InternalMessage::create([
                'farm_owner_id' => $farmOwner->id,
                'sender_id' => Auth::id(),
                'sender_role' => 'hr',
                'recipient_role' => 'finance',
                'message_type' => 'payroll_approval',
                'subject' => 'Payroll computation ready for finance approval',
                'message' => 'Payroll ' . $payroll->payroll_period . ' was prepared by HR and is now awaiting finance review.',
            ]);
        }

        return redirect()->route('payroll.index')->with('success', 'Payroll record created.');
    }

    public function show(Payroll $payroll)
    {
        $farmOwner = $this->getFarmOwner();
        abort_if($payroll->farm_owner_id !== $farmOwner->id, 403);

        $payroll->load(['employee', 'processedBy', 'financeApprovedBy', 'ownerApprovedBy', 'payslipReleasedBy']);

        $editRequests = $payroll->editRequests()
            ->with(['requester:id,name,role', 'reviewer:id,name,role'])
            ->latest()
            ->get();

        return view('farmowner.payroll.show', compact('payroll', 'editRequests'));
    }

    public function approve(Payroll $payroll)
    {
        $farmOwner = $this->getFarmOwner();
        abort_if($payroll->farm_owner_id !== $farmOwner->id, 403);
        abort_unless(Auth::user()?->isFarmOwner(), 403, 'Only the farm owner can approve payroll.');

        if (($payroll->workflow_status ?? 'draft') !== 'finance_approved') {
            return back()->with('error', 'Farm owner approval can only be done after finance approval.');
        }

        $payroll->update([
            'status' => 'approved',
            'owner_approved_by' => Auth::id(),
            'owner_approved_at' => now(),
            'workflow_status' => 'owner_approved',
        ]);

        return redirect()->route('payroll.show', $payroll)->with('success', 'Payroll approved by farm owner.');
    }

    public function markPaid(Request $request, Payroll $payroll)
    {
        $farmOwner = $this->getFarmOwner();
        abort_if($payroll->farm_owner_id !== $farmOwner->id, 403);
        abort_unless(Auth::user()?->isFarmOwner(), 403, 'Only the farm owner can mark payroll as paid.');

        if (($payroll->workflow_status ?? '') !== 'ready_for_disbursement') {
            return back()->with('error', 'Use Prepare Disbursement first, then let finance execute payout.');
        }

        return back()->with('error', 'Mark as paid is disabled for controlled disbursement workflow. Use Execute Disbursement.');
    }

    public function prepareDisbursement(Request $request, Payroll $payroll)
    {
        $farmOwner = $this->getFarmOwner();
        abort_if($payroll->farm_owner_id !== $farmOwner->id, 403);
        abort_unless(Auth::user()?->isFarmOwner(), 403, 'Only the farm owner can prepare payroll disbursement.');

        if (($payroll->workflow_status ?? '') !== 'released') {
            return back()->with('error', 'Disbursement can only be prepared after finance releases the payslip.');
        }

        if ($payroll->status !== 'approved') {
            return back()->with('error', 'Only approved payroll can be prepared for disbursement.');
        }

        $validated = $request->validate([
            'payment_method' => 'required|in:cash,bank_transfer,check,gcash',
        ]);

        $payroll->update([
            'payment_method' => $validated['payment_method'],
            'workflow_status' => 'ready_for_disbursement',
            'disbursement_prepared_by' => Auth::id(),
            'disbursement_prepared_at' => now(),
        ]);

        InternalMessage::create([
            'farm_owner_id' => $farmOwner->id,
            'sender_id' => Auth::id(),
            'sender_role' => 'farm_owner',
            'recipient_role' => 'finance',
            'message_type' => 'payroll_approval',
            'subject' => 'Payroll ready for disbursement execution',
            'message' => 'Payroll ' . $payroll->payroll_period . ' is now ready_for_disbursement and awaiting finance execution.',
        ]);

        return redirect()->route('payroll.show', $payroll)->with('success', 'Payroll moved to ready for disbursement.');
    }

    public function executeDisbursement(Request $request, Payroll $payroll)
    {
        $farmOwner = $this->getFarmOwner();
        abort_if($payroll->farm_owner_id !== $farmOwner->id, 403);
        abort_unless(Auth::user()?->isFinance(), 403, 'Only finance can execute payroll disbursement.');

        if (($payroll->workflow_status ?? '') !== 'ready_for_disbursement') {
            return back()->with('error', 'Payroll must be in ready_for_disbursement before execution.');
        }

        if ($payroll->status !== 'approved') {
            return back()->with('error', 'Only approved payroll can be disbursed.');
        }

        if ($payroll->disbursement_prepared_by && (int) $payroll->disbursement_prepared_by === (int) Auth::id()) {
            return back()->with('error', 'Second approver required: preparer cannot execute disbursement.');
        }

        $validated = $request->validate([
            'disbursement_reference' => 'nullable|string|max:120',
        ]);

        if (in_array((string) $payroll->payment_method, ['bank_transfer', 'check', 'gcash'], true)
            && blank($validated['disbursement_reference'] ?? null)) {
            return back()->with('error', 'Reference number is required for non-cash disbursements.');
        }

        $payroll->update([
            'status' => 'paid',
            'workflow_status' => 'paid',
            'pay_date' => today(),
            'disbursed_by' => Auth::id(),
            'disbursed_at' => now(),
            'disbursement_reference' => $validated['disbursement_reference'] ?? null,
        ]);

        Expense::updateOrCreate(
            [
                'source_type' => 'payroll',
                'source_id' => $payroll->id,
            ],
            [
                'farm_owner_id' => $farmOwner->id,
                'recorded_by' => Auth::id(),
                'category' => 'labor',
                'description' => 'Payroll payment for ' . ($payroll->payroll_period ?? ('Payroll #' . $payroll->id)),
                'amount' => $payroll->net_pay,
                'tax_amount' => 0,
                'total_amount' => $payroll->net_pay,
                'expense_date' => $payroll->pay_date ?? today(),
                'payment_status' => 'paid',
                'payment_method' => $payroll->payment_method,
                'reference_number' => $payroll->disbursement_reference ?: $payroll->payroll_period,
                'status' => 'approved',
                'is_auto_generated' => true,
                'notes' => 'Auto-generated from payroll mark paid action.',
            ]
        );

        Cache::forget("farm_{$farmOwner->id}_expense_stats");

        return redirect()->route('payroll.show', $payroll)->with('success', 'Payroll disbursement executed and marked as paid.');
    }

    public function financeApprove(Payroll $payroll)
    {
        $farmOwner = $this->getFarmOwner();
        abort_if($payroll->farm_owner_id !== $farmOwner->id, 403);
        abort_unless(Auth::user()?->isFinance(), 403, 'Only finance can approve payroll computations.');

        $payroll->update([
            'workflow_status' => 'finance_approved',
            'status' => 'pending',
            'finance_approved_by' => Auth::id(),
            'finance_approved_at' => now(),
        ]);

        InternalMessage::create([
            'farm_owner_id' => $farmOwner->id,
            'sender_id' => Auth::id(),
            'sender_role' => 'finance',
            'recipient_role' => 'farm_owner',
            'message_type' => 'payroll_approval',
            'subject' => 'Payroll approved by finance',
            'message' => 'Payroll ' . $payroll->payroll_period . ' was reviewed and approved by finance.',
        ]);

        return redirect()->route('payroll.show', $payroll)->with('success', 'Payroll approved by finance.');
    }

    public function releasePayslip(Payroll $payroll)
    {
        $farmOwner = $this->getFarmOwner();
        abort_if($payroll->farm_owner_id !== $farmOwner->id, 403);
        abort_unless(Auth::user()?->isFinance(), 403, 'Only finance can release payslips.');

        if ($payroll->workflow_status !== 'owner_approved') {
            return back()->with('error', 'Payslip can only be released after farm owner approval.');
        }

        $payroll->update([
            'workflow_status' => 'released',
            'payslip_released_by' => Auth::id(),
            'payslip_released_at' => now(),
        ]);

        InternalMessage::create([
            'farm_owner_id' => $farmOwner->id,
            'sender_id' => Auth::id(),
            'sender_role' => 'finance',
            'recipient_role' => 'hr',
            'message_type' => 'general',
            'subject' => 'Payslip released to employee',
            'message' => 'Payroll ' . $payroll->payroll_period . ' payslip was released by finance.',
        ]);

        return redirect()->route('payroll.show', $payroll)->with('success', 'Payslip released by finance.');
    }

    public function requestEdit(Request $request, Payroll $payroll)
    {
        $farmOwner = $this->getFarmOwner();
        abort_if($payroll->farm_owner_id !== $farmOwner->id, 403);
        abort_unless(Auth::user()?->isHR(), 403, 'Only HR can request manual payroll edits.');

        $validated = $request->validate([
            'reason' => 'required|string|max:1000',
            'basic_pay' => 'required|numeric|min:0',
            'overtime_pay' => 'required|numeric|min:0',
            'holiday_pay' => 'required|numeric|min:0',
            'allowances' => 'required|numeric|min:0',
            'bonuses' => 'required|numeric|min:0',
            'sss_deduction' => 'required|numeric|min:0',
            'philhealth_deduction' => 'required|numeric|min:0',
            'pagibig_deduction' => 'required|numeric|min:0',
            'tax_deduction' => 'required|numeric|min:0',
            'late_deduction' => 'required|numeric|min:0',
            'loan_deduction' => 'required|numeric|min:0',
            'insurance_deduction' => 'required|numeric|min:0',
            'reimbursement_deduction' => 'required|numeric|min:0',
            'other_deductions' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        PayrollEditRequest::create([
            'farm_owner_id' => $farmOwner->id,
            'payroll_id' => $payroll->id,
            'requested_by' => Auth::id(),
            'status' => 'pending',
            'reason' => $validated['reason'],
            'requested_changes' => collect($validated)
                ->except(['reason'])
                ->toArray(),
        ]);

        InternalMessage::create([
            'farm_owner_id' => $farmOwner->id,
            'sender_id' => Auth::id(),
            'sender_role' => 'hr',
            'recipient_role' => 'farm_owner',
            'message_type' => 'payslip_edit_request',
            'subject' => 'Payroll edit request for ' . $payroll->payroll_period,
            'message' => $validated['reason'],
        ]);

        return redirect()->route('payroll.show', $payroll)->with('success', 'Edit request submitted to farm owner for approval.');
    }

    public function approveEditRequest(PayrollEditRequest $editRequest)
    {
        $farmOwner = $this->getFarmOwner();
        abort_if($editRequest->farm_owner_id !== $farmOwner->id, 403);
        abort_unless(Auth::user()?->isFarmOwner(), 403, 'Only farm owner can approve HR edit requests.');
        abort_if($editRequest->status !== 'pending', 422, 'This request was already reviewed.');

        DB::transaction(function () use ($editRequest) {
            $payroll = $editRequest->payroll()->lockForUpdate()->firstOrFail();
            $changes = $editRequest->requested_changes ?? [];

            $payroll->fill($changes);
            $payroll->recalculateTotals();
            $payroll->owner_approved_by = Auth::id();
            $payroll->owner_approved_at = now();
            $payroll->save();

            $editRequest->update([
                'status' => 'approved',
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now(),
            ]);
        });

        return back()->with('success', 'Payroll edit request approved and applied.');
    }

    public function rejectEditRequest(Request $request, PayrollEditRequest $editRequest)
    {
        $farmOwner = $this->getFarmOwner();
        abort_if($editRequest->farm_owner_id !== $farmOwner->id, 403);
        abort_unless(Auth::user()?->isFarmOwner(), 403, 'Only farm owner can reject HR edit requests.');
        abort_if($editRequest->status !== 'pending', 422, 'This request was already reviewed.');

        $validated = $request->validate([
            'review_note' => 'nullable|string|max:1000',
        ]);

        $editRequest->update([
            'status' => 'rejected',
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
            'reason' => trim(($editRequest->reason ?? '') . "\n[Owner Note] " . ($validated['review_note'] ?? '')),
        ]);

        return back()->with('success', 'Payroll edit request rejected.');
    }

    public function generateBatch(Request $request)
    {
        $farmOwner = $this->getFarmOwner();

        $validated = $request->validate([
            'period_start' => 'required|date',
            'period_end' => 'required|date|after:period_start',
            'employee_ids' => 'nullable|array',
            'employee_ids.*' => 'exists:employees,id',
        ]);

        $periodStart = Carbon::parse($validated['period_start']);
        $periodEnd = Carbon::parse($validated['period_end']);

        $employees = Employee::byFarmOwner($farmOwner->id)
            ->active()
            ->when(!empty($validated['employee_ids']), fn($q) => $q->whereIn('id', $validated['employee_ids']))
            ->get();

        $generated = 0;

        foreach ($employees as $employee) {
            // Get attendance for period
            $attendance = Attendance::where('employee_id', $employee->id)
                ->byDateRange($periodStart, $periodEnd)
                ->whereIn('status', ['present', 'late', 'half_day'])
                ->get();

            $daysWorked = $attendance->where('status', 'present')->count()
                + $attendance->where('status', 'late')->count()
                + ($attendance->where('status', 'half_day')->count() * 0.5);

            $overtimeHours = $attendance->sum('overtime_hours');

            // Calculate pay
            $dailyRate = (float) ($employee->daily_rate ?: ((float) $employee->monthly_salary / 26));
            $basicPay = $dailyRate * $daysWorked;
            $hourlyRate = $dailyRate / 8;
            [$rating, $bonusPerBlock, $otBlocks, $otMultiplier] =
                $this->buildOvertimeRateInfo($employee, $overtimeHours);

            $overtimePay = $overtimeHours * ($hourlyRate * $otMultiplier);
            $lateMinutes = (float) $attendance->sum('late_minutes');
            $lateDeduction = round(($lateMinutes / 60) * $hourlyRate, 2);

            // Generate payroll
            $count = Payroll::byFarmOwner($farmOwner->id)->whereYear('created_at', now()->year)->count() + 1;
            
            Payroll::create([
                'farm_owner_id' => $farmOwner->id,
                'employee_id' => $employee->id,
                'processed_by' => Auth::id(),
                'payroll_period' => 'PAY-' . now()->format('Y') . '-' . str_pad($count, 5, '0', STR_PAD_LEFT),
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'pay_date' => $periodEnd,
                'days_worked' => $daysWorked,
                'hours_worked' => (float) $attendance->sum('hours_worked'),
                'regular_hours' => max(0, (float) $attendance->sum('hours_worked') - $overtimeHours),
                'overtime_hours' => $overtimeHours,
                'hourly_rate' => $hourlyRate,
                'basic_pay' => $basicPay,
                'overtime_pay' => $overtimePay,
                'late_deduction' => $lateDeduction,
                'gross_pay' => $basicPay + $overtimePay,
                'total_deductions' => $lateDeduction,
                'net_pay' => ($basicPay + $overtimePay) - $lateDeduction,
                'workflow_status' => Auth::user()?->isHR() ? 'pending_finance' : 'finance_approved',
                'status' => 'pending',
                'notes' => sprintf(
                    'OT Policy -> rating:%d, completed_30m_blocks:%d, bonus_per_30m:%.3f, ot_multiplier:%.3f, cap:%.2f',
                    $rating,
                    $otBlocks,
                    $bonusPerBlock,
                    $otMultiplier,
                    self::MAX_OVERTIME_MULTIPLIER
                ),
            ]);

            $generated++;
        }

        return redirect()->route('payroll.index')->with('success', "{$generated} payroll records generated.");
    }

    private function buildAttendancePayrollMetrics(
        int $employeeId,
        Carbon $periodStart,
        Carbon $periodEnd,
        float $daysWorked,
        float $basicPay,
    ): array {
        $attendance = Attendance::where('employee_id', $employeeId)
            ->byDateRange($periodStart, $periodEnd)
            ->get();

        $hoursWorked = (float) $attendance->sum('hours_worked');
        $overtimeHours = (float) $attendance->sum('overtime_hours');
        $regularHours = max(0, $hoursWorked - $overtimeHours);
        $lateMinutes = (float) $attendance->sum('late_minutes');

        $hourlyRate = 0.0;
        if ($regularHours > 0) {
            $hourlyRate = $basicPay / $regularHours;
        } elseif ($daysWorked > 0) {
            $hourlyRate = ($basicPay / $daysWorked) / 8;
        }

        $lateDeduction = round(($lateMinutes / 60) * $hourlyRate, 2);

        return [
            round($hoursWorked, 2),
            round($regularHours, 2),
            round($overtimeHours, 2),
            round($lateMinutes, 2),
            $lateDeduction,
            round($hourlyRate, 4),
        ];
    }

    private function buildOvertimeRateInfo(?Employee $employee, float $overtimeHours): array
    {
        $rating = (int) ($employee?->performance_rating ?? 3);
        $rating = max(1, min(5, $rating));

        // Every completed 30-minute overtime block increases the multiplier by rating tier.
        $bonusPerBlock = $this->getOvertimeBonusPerBlock($rating);
        $blocks = (int) floor(max(0, $overtimeHours) * 2);
        $computedMultiplier = 1.25 + ($blocks * $bonusPerBlock);
        $multiplier = round(min(self::MAX_OVERTIME_MULTIPLIER, $computedMultiplier), 4);

        return [$rating, $bonusPerBlock, $blocks, $multiplier];
    }

    private function getOvertimeBonusPerBlock(int $rating): float
    {
        return match ($rating) {
            5 => 0.06,
            4 => 0.04,
            3 => 0.025,
            2 => 0.01,
            default => 0.0,
        };
    }
}
