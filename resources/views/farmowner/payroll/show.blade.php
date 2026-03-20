@extends(auth()->user()?->isHR() ? 'hr.layouts.app' : 'farmowner.layouts.app')

@section('title', 'Payroll Details')
@section('header', 'Payroll Details')
@section('subheader', $payroll->payroll_period)

@section('header-actions')
<div class="flex flex-wrap gap-2">
    @if(Auth::user()?->isFarmOwner() && ($payroll->workflow_status ?? '') === 'finance_approved')
    <form method="POST" action="{{ route('payroll.approve', $payroll) }}">
        @csrf
        <button type="submit" class="px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700">Owner Approve Payroll</button>
    </form>
    @endif

    @if(Auth::user()?->isFinance() && ($payroll->workflow_status ?? 'draft') === 'pending_finance')
    <form method="POST" action="{{ route('payroll.financeApprove', $payroll) }}">
        @csrf
        <button type="submit" class="px-4 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700">Finance Approve</button>
    </form>
    @endif

    @if(Auth::user()?->isFinance() && ($payroll->workflow_status ?? '') === 'owner_approved')
    <form method="POST" action="{{ route('payroll.releasePayslip', $payroll) }}">
        @csrf
        <button type="submit" class="px-4 py-2 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700">Release Payslip</button>
    </form>
    @endif

    @if(Auth::user()?->isFarmOwner() && ($payroll->workflow_status ?? '') === 'released' && $payroll->status === 'approved')
    <form method="POST" action="{{ route('payroll.prepareDisbursement', $payroll) }}" class="flex gap-2">
        @csrf
        <select name="payment_method" class="rounded-lg border border-gray-600 bg-gray-700 px-3 py-2 text-white focus:ring-2 focus:ring-green-500">
            <option value="cash">Cash</option>
            <option value="bank_transfer">Bank Transfer</option>
            <option value="check">Check</option>
            <option value="gcash">GCash</option>
        </select>
        <button type="submit" class="px-4 py-2 rounded-lg bg-cyan-600 text-white hover:bg-cyan-700">Prepare Disbursement</button>
    </form>
    @endif

    @if(Auth::user()?->isFinance() && ($payroll->workflow_status ?? '') === 'ready_for_disbursement' && $payroll->status === 'approved')
    <form method="POST" action="{{ route('payroll.executeDisbursement', $payroll) }}" class="flex gap-2">
        @csrf
        <input
            type="text"
            name="disbursement_reference"
            placeholder="Reference # (required for non-cash)"
            class="rounded-lg border border-gray-600 bg-gray-700 px-3 py-2 text-white"
        >
        <button type="submit" class="px-4 py-2 rounded-lg bg-green-600 text-white hover:bg-green-700">Execute Disbursement</button>
    </form>
    @endif

    <a href="{{ route('payroll.index') }}" class="px-4 py-2 rounded-lg bg-gray-600 text-white hover:bg-gray-500">Back</a>
</div>
@endsection

@section('content')
@unless(Auth::user()?->isFarmOwner())
<div class="mb-6 rounded-lg border border-blue-700 bg-blue-900/30 px-4 py-3 text-sm text-blue-200">
    HR prepares payroll, finance reviews and requests owner approval, then payout is executed only after owner approval and disbursement readiness.
</div>
@endunless

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 rounded-lg border border-gray-700 bg-gray-800 p-6">
        <h3 class="mb-4 text-lg font-bold text-white">Payroll Summary</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div><p class="text-gray-400">Payroll Period</p><p class="font-semibold text-white">{{ $payroll->payroll_period }}</p></div>
            <div><p class="text-gray-400">Status</p><p class="font-semibold text-white">{{ ucfirst($payroll->status) }}</p></div>
            <div><p class="text-gray-400">Workflow</p><p class="font-semibold text-white">{{ ucfirst(str_replace('_', ' ', $payroll->workflow_status ?? 'draft')) }}</p></div>
            <div><p class="text-gray-400">Employee</p><p class="font-semibold text-white">{{ $payroll->employee?->full_name ?? 'N/A' }}</p></div>
            <div><p class="text-gray-400">Position</p><p class="font-semibold text-white">{{ $payroll->employee?->position ?? 'N/A' }}</p></div>
            <div><p class="text-gray-400">Performance Rating</p><p class="font-semibold text-white">{{ $payroll->employee?->performance_rating ?? 3 }}/5</p></div>
            <div><p class="text-gray-400">Period Start</p><p class="font-semibold text-white">{{ $payroll->period_start?->format('M d, Y') }}</p></div>
            <div><p class="text-gray-400">Period End</p><p class="font-semibold text-white">{{ $payroll->period_end?->format('M d, Y') }}</p></div>
            <div><p class="text-gray-400">Processed By</p><p class="font-semibold text-white">{{ $payroll->processedBy?->name ?? 'N/A' }}</p></div>
            <div><p class="text-gray-400">Pay Date</p><p class="font-semibold text-white">{{ $payroll->pay_date?->format('M d, Y') ?? 'Not yet paid' }}</p></div>
            <div><p class="text-gray-400">Finance Approved By</p><p class="font-semibold text-white">{{ $payroll->financeApprovedBy?->name ?? 'Pending' }}</p></div>
            <div><p class="text-gray-400">Owner Approved By</p><p class="font-semibold text-white">{{ $payroll->ownerApprovedBy?->name ?? 'Pending' }}</p></div>
            <div><p class="text-gray-400">Payslip Released At</p><p class="font-semibold text-white">{{ $payroll->payslip_released_at?->format('M d, Y h:i A') ?? 'Not yet released' }}</p></div>
            <div><p class="text-gray-400">Prepared For Disbursement By</p><p class="font-semibold text-white">{{ $payroll->disbursementPreparedBy?->name ?? 'Pending' }}</p></div>
            <div><p class="text-gray-400">Disbursed By</p><p class="font-semibold text-white">{{ $payroll->disbursedBy?->name ?? 'Pending' }}</p></div>
            <div><p class="text-gray-400">Disbursement Reference</p><p class="font-semibold text-white">{{ $payroll->disbursement_reference ?? 'N/A' }}</p></div>
        </div>
    </div>

    <div class="rounded-lg border border-gray-700 bg-gray-800 p-6">
        <h3 class="mb-4 text-lg font-bold text-white">Net Pay</h3>
        <p class="text-3xl font-bold text-green-500">₱{{ number_format($payroll->net_pay ?? 0, 2) }}</p>
        <div class="mt-4 space-y-2 text-sm">
            <div class="flex justify-between"><span class="text-gray-400">Gross Pay</span><span class="text-white">₱{{ number_format($payroll->gross_pay ?? 0, 2) }}</span></div>
            <div class="flex justify-between"><span class="text-gray-400">Total Deductions</span><span class="text-red-300">₱{{ number_format($payroll->total_deductions ?? 0, 2) }}</span></div>
            <div class="flex justify-between"><span class="text-gray-400">Hours Worked</span><span class="text-white">{{ number_format($payroll->hours_worked ?? 0, 2) }}</span></div>
            <div class="flex justify-between"><span class="text-gray-400">Regular Hours</span><span class="text-white">{{ number_format($payroll->regular_hours ?? 0, 2) }}</span></div>
            <div class="flex justify-between"><span class="text-gray-400">Overtime Hours</span><span class="text-white">{{ number_format($payroll->overtime_hours ?? 0, 2) }}</span></div>
            <div class="flex justify-between"><span class="text-gray-400">Hourly Rate</span><span class="text-white">₱{{ number_format($payroll->hourly_rate ?? 0, 4) }}</span></div>
            <div class="flex justify-between"><span class="text-gray-400">Payment Method</span><span class="text-white">{{ $payroll->payment_method ? ucfirst(str_replace('_', ' ', $payroll->payment_method)) : 'Pending' }}</span></div>
        </div>
    </div>
</div>

<div class="mt-6 rounded-lg border border-gray-700 bg-gray-800 p-6">
    <h3 class="mb-4 text-lg font-bold text-white">Earnings and Deductions</h3>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
        <div>
            <h4 class="mb-3 font-semibold text-green-400">Earnings</h4>
            <div class="space-y-2">
                <div class="flex justify-between"><span class="text-gray-400">Basic Pay</span><span class="text-white">₱{{ number_format($payroll->basic_pay ?? 0, 2) }}</span></div>
                <div class="flex justify-between"><span class="text-gray-400">Overtime Pay</span><span class="text-white">₱{{ number_format($payroll->overtime_pay ?? 0, 2) }}</span></div>
                <div class="flex justify-between"><span class="text-gray-400">Holiday Pay</span><span class="text-white">₱{{ number_format($payroll->holiday_pay ?? 0, 2) }}</span></div>
                <div class="flex justify-between"><span class="text-gray-400">Allowances</span><span class="text-white">₱{{ number_format($payroll->allowances ?? 0, 2) }}</span></div>
                <div class="flex justify-between"><span class="text-gray-400">Bonuses</span><span class="text-white">₱{{ number_format($payroll->bonuses ?? 0, 2) }}</span></div>
            </div>
        </div>
        <div>
            <h4 class="mb-3 font-semibold text-red-400">Deductions</h4>
            <div class="space-y-2">
                <div class="flex justify-between"><span class="text-gray-400">SSS</span><span class="text-white">₱{{ number_format($payroll->sss_deduction ?? 0, 2) }}</span></div>
                <div class="flex justify-between"><span class="text-gray-400">PhilHealth</span><span class="text-white">₱{{ number_format($payroll->philhealth_deduction ?? 0, 2) }}</span></div>
                <div class="flex justify-between"><span class="text-gray-400">Pag-IBIG</span><span class="text-white">₱{{ number_format($payroll->pagibig_deduction ?? 0, 2) }}</span></div>
                <div class="flex justify-between"><span class="text-gray-400">Tax</span><span class="text-white">₱{{ number_format($payroll->tax_deduction ?? 0, 2) }}</span></div>
                <div class="flex justify-between"><span class="text-gray-400">Late Deduction</span><span class="text-white">₱{{ number_format($payroll->late_deduction ?? 0, 2) }}</span></div>
                <div class="flex justify-between"><span class="text-gray-400">Loan</span><span class="text-white">₱{{ number_format($payroll->loan_deduction ?? 0, 2) }}</span></div>
                <div class="flex justify-between"><span class="text-gray-400">Insurance</span><span class="text-white">₱{{ number_format($payroll->insurance_deduction ?? 0, 2) }}</span></div>
                <div class="flex justify-between"><span class="text-gray-400">Reimbursements</span><span class="text-white">₱{{ number_format($payroll->reimbursement_deduction ?? 0, 2) }}</span></div>
                <div class="flex justify-between"><span class="text-gray-400">Other</span><span class="text-white">₱{{ number_format($payroll->other_deductions ?? 0, 2) }}</span></div>
            </div>
        </div>
    </div>

    @if($payroll->notes)
    <div class="mt-6 rounded-lg border border-gray-700 bg-gray-900/40 p-4">
        <p class="mb-1 text-sm font-medium text-gray-300">Notes</p>
        <p class="text-sm text-gray-400">{{ $payroll->notes }}</p>
    </div>
    @endif

    <div class="mt-4 rounded-lg border border-gray-700 bg-gray-900/40 p-4 text-sm">
        <p class="mb-2 font-medium text-gray-300">Overtime Policy Applied</p>
        <ul class="space-y-1 text-gray-400">
            <li>Base OT rate starts at 1.25x hourly rate.</li>
            <li>Additional bonus is added per completed 30-minute OT block based on employee performance rating.</li>
            <li>Rating 5: +6% per 30m, Rating 4: +4%, Rating 3: +2.5%, Rating 2: +1%, Rating 1: +0%.</li>
            <li>Break time is deducted from work hours, including overnight/midnight-crossing breaks when entered.</li>
        </ul>
    </div>
</div>

@if(Auth::user()?->isHR())
<div class="mt-6 rounded-lg border border-gray-700 bg-gray-800 p-6">
    <h3 class="mb-4 text-lg font-bold text-white">Request Manual Payslip Edit (Owner Approval Required)</h3>
    <form method="POST" action="{{ route('payroll.requestEdit', $payroll) }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
        @csrf
        <div>
            <label class="block text-xs text-gray-400 mb-1">Basic Pay</label>
            <input type="number" step="0.01" min="0" name="basic_pay" value="{{ $payroll->basic_pay }}" class="w-full rounded-lg border border-gray-600 bg-gray-700 px-3 py-2 text-white">
        </div>
        <div>
            <label class="block text-xs text-gray-400 mb-1">Overtime Pay</label>
            <input type="number" step="0.01" min="0" name="overtime_pay" value="{{ $payroll->overtime_pay }}" class="w-full rounded-lg border border-gray-600 bg-gray-700 px-3 py-2 text-white">
        </div>
        <div>
            <label class="block text-xs text-gray-400 mb-1">Holiday Pay</label>
            <input type="number" step="0.01" min="0" name="holiday_pay" value="{{ $payroll->holiday_pay }}" class="w-full rounded-lg border border-gray-600 bg-gray-700 px-3 py-2 text-white">
        </div>
        <div>
            <label class="block text-xs text-gray-400 mb-1">Allowances</label>
            <input type="number" step="0.01" min="0" name="allowances" value="{{ $payroll->allowances }}" class="w-full rounded-lg border border-gray-600 bg-gray-700 px-3 py-2 text-white">
        </div>
        <div>
            <label class="block text-xs text-gray-400 mb-1">Bonuses</label>
            <input type="number" step="0.01" min="0" name="bonuses" value="{{ $payroll->bonuses }}" class="w-full rounded-lg border border-gray-600 bg-gray-700 px-3 py-2 text-white">
        </div>
        <div>
            <label class="block text-xs text-gray-400 mb-1">SSS</label>
            <input type="number" step="0.01" min="0" name="sss_deduction" value="{{ $payroll->sss_deduction }}" class="w-full rounded-lg border border-gray-600 bg-gray-700 px-3 py-2 text-white">
        </div>
        <div>
            <label class="block text-xs text-gray-400 mb-1">PhilHealth</label>
            <input type="number" step="0.01" min="0" name="philhealth_deduction" value="{{ $payroll->philhealth_deduction }}" class="w-full rounded-lg border border-gray-600 bg-gray-700 px-3 py-2 text-white">
        </div>
        <div>
            <label class="block text-xs text-gray-400 mb-1">Pag-IBIG</label>
            <input type="number" step="0.01" min="0" name="pagibig_deduction" value="{{ $payroll->pagibig_deduction }}" class="w-full rounded-lg border border-gray-600 bg-gray-700 px-3 py-2 text-white">
        </div>
        <div>
            <label class="block text-xs text-gray-400 mb-1">Tax</label>
            <input type="number" step="0.01" min="0" name="tax_deduction" value="{{ $payroll->tax_deduction }}" class="w-full rounded-lg border border-gray-600 bg-gray-700 px-3 py-2 text-white">
        </div>
        <div>
            <label class="block text-xs text-gray-400 mb-1">Late Deduction</label>
            <input type="number" step="0.01" min="0" name="late_deduction" value="{{ $payroll->late_deduction }}" class="w-full rounded-lg border border-gray-600 bg-gray-700 px-3 py-2 text-white">
        </div>
        <div>
            <label class="block text-xs text-gray-400 mb-1">Loan Deduction</label>
            <input type="number" step="0.01" min="0" name="loan_deduction" value="{{ $payroll->loan_deduction }}" class="w-full rounded-lg border border-gray-600 bg-gray-700 px-3 py-2 text-white">
        </div>
        <div>
            <label class="block text-xs text-gray-400 mb-1">Insurance Deduction</label>
            <input type="number" step="0.01" min="0" name="insurance_deduction" value="{{ $payroll->insurance_deduction }}" class="w-full rounded-lg border border-gray-600 bg-gray-700 px-3 py-2 text-white">
        </div>
        <div>
            <label class="block text-xs text-gray-400 mb-1">Reimbursement Deduction</label>
            <input type="number" step="0.01" min="0" name="reimbursement_deduction" value="{{ $payroll->reimbursement_deduction }}" class="w-full rounded-lg border border-gray-600 bg-gray-700 px-3 py-2 text-white">
        </div>
        <div>
            <label class="block text-xs text-gray-400 mb-1">Other Deduction</label>
            <input type="number" step="0.01" min="0" name="other_deductions" value="{{ $payroll->other_deductions }}" class="w-full rounded-lg border border-gray-600 bg-gray-700 px-3 py-2 text-white">
        </div>
        <div class="md:col-span-3">
            <label class="block text-xs text-gray-400 mb-1">Reason for Edit Request</label>
            <textarea name="reason" rows="3" class="w-full rounded-lg border border-gray-600 bg-gray-700 px-3 py-2 text-white" required></textarea>
        </div>
        <div class="md:col-span-3">
            <label class="block text-xs text-gray-400 mb-1">Updated Notes</label>
            <textarea name="notes" rows="2" class="w-full rounded-lg border border-gray-600 bg-gray-700 px-3 py-2 text-white">{{ $payroll->notes }}</textarea>
        </div>
        <div class="md:col-span-3">
            <button type="submit" class="rounded-lg bg-orange-600 px-4 py-2 text-white hover:bg-orange-700">Submit Edit Request</button>
        </div>
    </form>
</div>
@endif

@if(Auth::user()?->isFarmOwner() && isset($editRequests) && $editRequests->count())
<div class="mt-6 rounded-lg border border-gray-700 bg-gray-800 p-6">
    <h3 class="mb-4 text-lg font-bold text-white">HR Edit Requests</h3>
    <div class="space-y-4">
        @foreach($editRequests as $req)
        <div class="rounded-lg border border-gray-700 p-4">
            <div class="flex items-center justify-between mb-2">
                <p class="text-sm text-white font-semibold">Requested by {{ $req->requester?->name ?? 'HR' }}</p>
                <span class="text-xs px-2 py-1 rounded {{ $req->status === 'approved' ? 'bg-green-900 text-green-300' : ($req->status === 'rejected' ? 'bg-red-900 text-red-300' : 'bg-yellow-900 text-yellow-300') }}">
                    {{ ucfirst($req->status) }}
                </span>
            </div>
            <p class="text-sm text-gray-300 whitespace-pre-line">{{ $req->reason }}</p>
            @if($req->status === 'pending')
            <div class="mt-3 flex gap-2">
                <form method="POST" action="{{ route('payroll.editRequests.approve', $req) }}">
                    @csrf
                    <button type="submit" class="rounded bg-green-600 px-3 py-1 text-sm text-white hover:bg-green-700">Approve & Apply</button>
                </form>
                <form method="POST" action="{{ route('payroll.editRequests.reject', $req) }}" class="flex gap-2">
                    @csrf
                    <input type="text" name="review_note" placeholder="Reason" class="rounded border border-gray-600 bg-gray-700 px-2 py-1 text-sm text-white">
                    <button type="submit" class="rounded bg-red-600 px-3 py-1 text-sm text-white hover:bg-red-700">Reject</button>
                </form>
            </div>
            @endif
        </div>
        @endforeach
    </div>
</div>
@endif
@endsection