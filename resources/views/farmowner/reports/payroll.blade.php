@extends('farmowner.layouts.app')

@section('title', 'Payroll Report')
@section('header', 'Payroll Report')
@section('subheader', 'Monthly payroll summary and department totals')

@section('content')
<div class="bg-gray-800 border border-gray-700 rounded-lg p-4 mb-6">
    <form method="GET" class="flex flex-wrap gap-4 items-end">
        <div>
            <label class="block text-sm text-gray-400 mb-1">Month</label>
            <input type="month" name="month" value="{{ $month->format('Y-m') }}" class="px-3 py-2 bg-gray-700 text-white border border-gray-600 rounded-lg">
        </div>
        <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">Apply</button>
    </form>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
    <div class="bg-gray-800 border border-gray-700 rounded-lg p-5 border-l-4 border-blue-600">
        <p class="text-sm text-gray-300">Gross Pay</p>
        <p class="text-2xl font-bold text-blue-500">PHP {{ number_format((float) $totalGross, 2) }}</p>
    </div>
    <div class="bg-gray-800 border border-gray-700 rounded-lg p-5 border-l-4 border-red-600">
        <p class="text-sm text-gray-300">Deductions</p>
        <p class="text-2xl font-bold text-red-500">PHP {{ number_format((float) $totalDeductions, 2) }}</p>
    </div>
    <div class="bg-gray-800 border border-gray-700 rounded-lg p-5 border-l-4 border-green-600">
        <p class="text-sm text-gray-300">Net Pay</p>
        <p class="text-2xl font-bold text-green-500">PHP {{ number_format((float) $totalNet, 2) }}</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <div class="bg-gray-800 border border-gray-700 rounded-lg overflow-x-auto">
        <div class="p-5 border-b border-gray-600">
            <h3 class="font-semibold text-lg">Payroll Entries</h3>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-gray-700 text-gray-300">
                <tr>
                    <th class="px-4 py-3 text-left">Employee</th>
                    <th class="px-4 py-3 text-left">Position</th>
                    <th class="px-4 py-3 text-right">Gross</th>
                    <th class="px-4 py-3 text-right">Deductions</th>
                    <th class="px-4 py-3 text-right">Net</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-700">
                @forelse($payrollSummary as $entry)
                    <tr>
                        <td class="px-4 py-3 text-white">{{ ($entry->employee->first_name ?? '') . ' ' . ($entry->employee->last_name ?? '') }}</td>
                        <td class="px-4 py-3 text-gray-300">{{ $entry->employee->position ?? '-' }}</td>
                        <td class="px-4 py-3 text-right text-blue-300">PHP {{ number_format((float) $entry->gross_pay, 2) }}</td>
                        <td class="px-4 py-3 text-right text-red-300">PHP {{ number_format((float) $entry->total_deductions, 2) }}</td>
                        <td class="px-4 py-3 text-right text-green-400">PHP {{ number_format((float) $entry->net_pay, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-6 text-center text-gray-400">No payroll entries for selected month.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="bg-gray-800 border border-gray-700 rounded-lg overflow-x-auto">
        <div class="p-5 border-b border-gray-600">
            <h3 class="font-semibold text-lg">By Department</h3>
        </div>
        <table class="w-full text-sm mb-4">
            <thead class="bg-gray-700 text-gray-300">
                <tr>
                    <th class="px-4 py-3 text-left">Department</th>
                    <th class="px-4 py-3 text-right">Net Pay Total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-700">
                @forelse($byDepartment as $department)
                    <tr>
                        <td class="px-4 py-3 text-white">{{ $department->department ?? 'Unassigned' }}</td>
                        <td class="px-4 py-3 text-right text-green-400">PHP {{ number_format((float) $department->total, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2" class="px-4 py-6 text-center text-gray-400">No department totals available.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="px-5 pb-5">
            <h4 class="font-semibold mb-2">Employee Headcount</h4>
            <div class="space-y-2 text-sm">
                @forelse($headcount as $status)
                    <div class="flex justify-between">
                        <span class="text-gray-300">{{ ucfirst($status->status) }}</span>
                        <span class="text-white font-semibold">{{ (int) $status->count }}</span>
                    </div>
                @empty
                    <p class="text-gray-400">No headcount data available.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
