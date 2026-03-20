@extends(auth()->user()?->isHR() ? 'hr.layouts.app' : 'farmowner.layouts.app')

@section('title', 'Payroll')
@section('header', 'Payroll Management')
@section('subheader', auth()->user()?->isHR() ? 'Prepare payroll records for farm-owner approval' : 'Process and manage employee payroll')

@section('header-actions')
<div class="flex gap-2">
    <a href="{{ route('payroll.create') }}" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">+ Create Payroll</a>
</div>
@endsection

@section('content')
<!-- Stats -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
    <div class="bg-gray-800 border border-gray-700 rounded-lg p-4 border-l-4 border-yellow-600">
        <p class="text-gray-400 text-xs">Pending Payroll</p>
        <p class="text-2xl font-bold text-yellow-600">₱{{ number_format($stats['pending'] ?? 0, 2) }}</p>
    </div>
    <div class="bg-gray-800 border border-gray-700 rounded-lg p-4 border-l-4 border-blue-600">
        <p class="text-gray-400 text-xs">Pending Finance Approval</p>
        <p class="text-2xl font-bold text-blue-600">₱{{ number_format($stats['pending_finance'] ?? 0, 2) }}</p>
    </div>
    <div class="bg-gray-800 border border-gray-700 rounded-lg p-4 border-l-4 border-green-600 md:col-span-2">
        <p class="text-gray-400 text-xs">Paid This Month</p>
        <p class="text-2xl font-bold text-green-600">₱{{ number_format($stats['paid_this_month'] ?? 0, 2) }}</p>
    </div>
</div>

<!-- Filter -->
<div class="bg-gray-800 border border-gray-700 rounded-lg p-4 mb-6">
    <form method="GET" class="flex flex-wrap gap-4">
        <select name="status" class="px-3 py-2 bg-gray-700 text-white border border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500">
            <option value="">All Status</option>
            <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
            <option value="pending_finance" {{ request('status') === 'pending_finance' ? 'selected' : '' }}>Pending Finance</option>
            <option value="finance_approved" {{ request('status') === 'finance_approved' ? 'selected' : '' }}>Finance Approved</option>
            <option value="released" {{ request('status') === 'released' ? 'selected' : '' }}>Payslip Released</option>
            <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
            <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Paid</option>
        </select>
        <input type="month" name="month" value="{{ request('month', now()->format('Y-m')) }}"
            class="px-3 py-2 bg-gray-700 text-white border border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500">
        <button type="submit" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-500">Filter</button>
    </form>
</div>

<!-- Table -->
<div class="bg-gray-800 border border-gray-700 rounded-lg">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-700">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Payroll #</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Employee</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Period</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Basic Pay</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Net Pay</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Workflow</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-600">
                @forelse($payrolls as $payroll)
                <tr class="hover:bg-gray-700">
                    <td class="px-6 py-4 font-mono text-sm">{{ $payroll->payroll_period }}</td>
                    <td class="px-6 py-4 font-medium text-white">{{ $payroll->employee?->full_name ?? 'N/A' }}</td>
                    <td class="px-6 py-4 text-gray-300 text-sm">
                        {{ $payroll->period_start->format('M d') }} - {{ $payroll->period_end->format('M d, Y') }}
                    </td>
                    <td class="px-6 py-4 text-gray-300">₱{{ number_format($payroll->basic_pay, 2) }}</td>
                    <td class="px-6 py-4 font-semibold text-green-600">₱{{ number_format($payroll->net_pay, 2) }}</td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 text-xs rounded-full 
                            @if($payroll->status === 'paid') bg-green-900 text-green-300
                            @elseif($payroll->status === 'approved') bg-blue-900 text-blue-300
                            @else bg-yellow-900 text-yellow-300 @endif">
                            {{ ucfirst($payroll->status) }}
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 text-xs rounded-full bg-gray-700 text-gray-300">
                            {{ ucfirst(str_replace('_', ' ', $payroll->workflow_status ?? 'draft')) }}
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <a href="{{ route('payroll.show', $payroll) }}" class="text-blue-400 hover:text-blue-300">View</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-6 py-8 text-center text-gray-400">No payroll records found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($payrolls->hasPages())
    <div class="p-6 border-t border-gray-600">{{ $payrolls->links() }}</div>
    @endif
</div>
@endsection
