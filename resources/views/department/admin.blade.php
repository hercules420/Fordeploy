@extends('department.layouts.app')

@section('title', 'Admin Dashboard')
@section('header', 'Admin Dashboard')

@section('sidebar-links')
    <a href="{{ route('department.admin.dashboard') }}"
       class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium bg-orange-500/20 text-orange-400 border border-orange-500/30">
        🏠 Dashboard
    </a>
    <a href="{{ route('suppliers.index') }}"
       class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium text-gray-300 hover:bg-gray-700 hover:text-white">
        🏭 Suppliers
    </a>
    <a href="{{ route('attendance.index') }}"
       class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium text-gray-300 hover:bg-gray-700 hover:text-white">
        📋 Attendance
    </a>
@endsection

@section('content')
{{-- Stats Cards --}}
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="bg-gray-800 border border-gray-700 rounded-lg p-4">
        <p class="text-xs text-gray-400 uppercase tracking-wide">Total Employees</p>
        <p class="text-2xl font-bold text-white mt-1">{{ $stats['total_employees'] }}</p>
    </div>
    <div class="bg-gray-800 border border-green-600/40 rounded-lg p-4">
        <p class="text-xs text-gray-400 uppercase tracking-wide">Active Employees</p>
        <p class="text-2xl font-bold text-green-400 mt-1">{{ $stats['active_employees'] }}</p>
    </div>
    <div class="bg-gray-800 border border-blue-600/40 rounded-lg p-4">
        <p class="text-xs text-gray-400 uppercase tracking-wide">Total Suppliers</p>
        <p class="text-2xl font-bold text-blue-400 mt-1">{{ $stats['total_suppliers'] }}</p>
    </div>
</div>

{{-- Quick Actions --}}
<div class="grid grid-cols-2 md:grid-cols-2 gap-4 mb-6">
    <a href="{{ route('suppliers.index') }}"
       class="bg-gray-800 border border-gray-700 hover:border-orange-500 rounded-lg p-4 transition group">
        <p class="text-lg font-semibold text-orange-400 group-hover:text-orange-300">🏭 Suppliers</p>
        <p class="text-sm text-gray-400 mt-1">View and manage farm suppliers</p>
    </a>
    <a href="{{ route('attendance.index') }}"
       class="bg-gray-800 border border-gray-700 hover:border-orange-500 rounded-lg p-4 transition group">
        <p class="text-lg font-semibold text-orange-400 group-hover:text-orange-300">📋 Attendance</p>
        <p class="text-sm text-gray-400 mt-1">Track employee attendance records</p>
    </a>
</div>

{{-- Recent Employees (read-only) --}}
<div class="bg-gray-800 border border-gray-700 rounded-lg overflow-hidden">
    <div class="px-5 py-4 border-b border-gray-700 flex items-center justify-between">
        <h3 class="text-base font-semibold text-white">Recent Employees</h3>
        <span class="text-xs text-gray-500">Hiring is restricted to Farm Owner and HR</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-700/50 text-gray-400 uppercase text-xs">
                <tr>
                    <th class="px-4 py-3 text-left">Employee ID</th>
                    <th class="px-4 py-3 text-left">Name</th>
                    <th class="px-4 py-3 text-left">Department</th>
                    <th class="px-4 py-3 text-left">Position</th>
                    <th class="px-4 py-3 text-left">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-700">
                @forelse($recentEmployees as $employee)
                <tr class="hover:bg-gray-700/30 transition">
                    <td class="px-4 py-3 font-mono text-gray-300">{{ $employee->employee_id }}</td>
                    <td class="px-4 py-3 text-white">{{ $employee->first_name }} {{ $employee->last_name }}</td>
                    <td class="px-4 py-3 text-gray-300">{{ ucfirst(str_replace('_', ' ', $employee->department)) }}</td>
                    <td class="px-4 py-3 text-gray-400">{{ $employee->position }}</td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $employee->status === 'active' ? 'bg-green-900 text-green-300' : 'bg-gray-700 text-gray-300' }}">
                            {{ ucfirst($employee->status) }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-4 py-8 text-center text-gray-500">No employees found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
