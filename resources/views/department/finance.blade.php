@extends('department.layouts.app')

@section('title', 'Finance Dashboard')
@section('header', 'Finance Dashboard')

@section('sidebar-links')
    <a href="{{ route('department.finance.dashboard') }}"
       class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium bg-orange-500/20 text-orange-400 border border-orange-500/30">
        🏠 Dashboard
    </a>
    <a href="{{ route('expenses.index') }}"
       class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium text-gray-300 hover:bg-gray-700 hover:text-white">
        💸 Expenses
    </a>
    <a href="{{ route('income.index') }}"
       class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium text-gray-300 hover:bg-gray-700 hover:text-white">
        💰 Income
    </a>
    <a href="{{ route('payroll.index') }}"
       class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium text-gray-300 hover:bg-gray-700 hover:text-white">
        👔 Payroll
    </a>
    <a href="{{ route('department.messages') }}"
       class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium text-gray-300 hover:bg-gray-700 hover:text-white">
        💬 Communication
    </a>
@endsection

@section('content')
{{-- Stats Cards --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-gray-800 border border-green-600/40 rounded-lg p-4">
        <p class="text-xs text-gray-400 uppercase tracking-wide">Total Income</p>
        <p class="text-2xl font-bold text-green-400 mt-1">₱{{ number_format($stats['total_income'], 2) }}</p>
    </div>
    <div class="bg-gray-800 border border-red-600/40 rounded-lg p-4">
        <p class="text-xs text-gray-400 uppercase tracking-wide">Total Expenses</p>
        <p class="text-2xl font-bold text-red-400 mt-1">₱{{ number_format($stats['total_expenses'], 2) }}</p>
    </div>
    <div class="bg-gray-800 border border-yellow-600/40 rounded-lg p-4">
        <p class="text-xs text-gray-400 uppercase tracking-wide">Pending Expenses</p>
        <p class="text-2xl font-bold text-yellow-400 mt-1">{{ $stats['pending_expenses'] }}</p>
    </div>
    <div class="bg-gray-800 border border-blue-600/40 rounded-lg p-4">
        <p class="text-xs text-gray-400 uppercase tracking-wide">Pending Income</p>
        <p class="text-2xl font-bold text-blue-400 mt-1">{{ $stats['pending_income'] }}</p>
    </div>
</div>

{{-- Quick Actions --}}
<div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-6">
    <a href="{{ route('expenses.index') }}"
       class="bg-gray-800 border border-gray-700 hover:border-orange-500 rounded-lg p-4 transition group">
        <p class="text-lg font-semibold text-orange-400 group-hover:text-orange-300">💸 Expenses</p>
        <p class="text-sm text-gray-400 mt-1">Track and manage farm expenses</p>
    </a>
    <a href="{{ route('income.index') }}"
       class="bg-gray-800 border border-gray-700 hover:border-orange-500 rounded-lg p-4 transition group">
        <p class="text-lg font-semibold text-orange-400 group-hover:text-orange-300">💰 Income Records</p>
        <p class="text-sm text-gray-400 mt-1">View income from sales and orders</p>
    </a>
    <a href="{{ route('payroll.index') }}"
       class="bg-gray-800 border border-gray-700 hover:border-orange-500 rounded-lg p-4 transition group">
        <p class="text-lg font-semibold text-orange-400 group-hover:text-orange-300">👔 Payroll</p>
        <p class="text-sm text-gray-400 mt-1">Employee salary and payroll records</p>
    </a>
</div>

{{-- Recent Expenses --}}
<div class="bg-gray-800 border border-gray-700 rounded-lg overflow-hidden">
    <div class="px-5 py-4 border-b border-gray-700 flex items-center justify-between">
        <h3 class="text-base font-semibold text-white">Recent Expenses</h3>
        <a href="{{ route('expenses.index') }}" class="text-sm text-orange-400 hover:underline">View all →</a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-700/50 text-gray-400 uppercase text-xs">
                <tr>
                    <th class="px-4 py-3 text-left">Expense #</th>
                    <th class="px-4 py-3 text-left">Category</th>
                    <th class="px-4 py-3 text-left">Amount</th>
                    <th class="px-4 py-3 text-left">Status</th>
                    <th class="px-4 py-3 text-left">Date</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-700">
                @forelse($recentExpenses as $expense)
                <tr class="hover:bg-gray-700/30 transition">
                    <td class="px-4 py-3 font-mono text-gray-300">{{ $expense->expense_number ?? '—' }}</td>
                    <td class="px-4 py-3 text-gray-300">{{ ucfirst($expense->category) }}</td>
                    <td class="px-4 py-3 text-white font-medium">₱{{ number_format($expense->amount, 2) }}</td>
                    <td class="px-4 py-3">
                        @php
                            $colors = [
                                'pending' => 'bg-yellow-900 text-yellow-300',
                                'paid'    => 'bg-green-900 text-green-300',
                                'overdue' => 'bg-red-900 text-red-300',
                                'partial' => 'bg-blue-900 text-blue-300',
                            ];
                            $color = $colors[$expense->payment_status] ?? 'bg-gray-700 text-gray-300';
                        @endphp
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $color }}">
                            {{ ucfirst($expense->payment_status) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-gray-400">
                        {{ \Carbon\Carbon::parse($expense->expense_date)->format('M d, Y') }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-4 py-8 text-center text-gray-500">No expenses found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
