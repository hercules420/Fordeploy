@extends('department.layouts.app')

@section('title', 'Logistics Dashboard')
@section('header', 'Logistics Dashboard')

@section('sidebar-links')
    <a href="{{ route('department.logistics.dashboard') }}"
       class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium bg-orange-500/20 text-orange-400 border border-orange-500/30">
        🏠 Dashboard
    </a>
    <a href="{{ route('deliveries.index') }}"
       class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium text-gray-300 hover:bg-gray-700 hover:text-white">
        🚚 Deliveries
    </a>
    <a href="{{ route('drivers.index') }}"
       class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium text-gray-300 hover:bg-gray-700 hover:text-white">
        👤 Drivers
    </a>
    <a href="{{ route('deliveries.schedule') }}"
       class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium text-gray-300 hover:bg-gray-700 hover:text-white">
        📅 Schedule
    </a>
@endsection

@section('content')
{{-- Stats Cards --}}
<div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
    <div class="bg-gray-800 border border-gray-700 rounded-lg p-4">
        <p class="text-xs text-gray-400 uppercase tracking-wide">Total Deliveries</p>
        <p class="text-2xl font-bold text-white mt-1">{{ $stats['total'] }}</p>
    </div>
    <div class="bg-gray-800 border border-yellow-600/40 rounded-lg p-4">
        <p class="text-xs text-gray-400 uppercase tracking-wide">Pending</p>
        <p class="text-2xl font-bold text-yellow-400 mt-1">{{ $stats['pending'] }}</p>
    </div>
    <div class="bg-gray-800 border border-blue-600/40 rounded-lg p-4">
        <p class="text-xs text-gray-400 uppercase tracking-wide">In Transit</p>
        <p class="text-2xl font-bold text-blue-400 mt-1">{{ $stats['in_transit'] }}</p>
    </div>
    <div class="bg-gray-800 border border-green-600/40 rounded-lg p-4">
        <p class="text-xs text-gray-400 uppercase tracking-wide">Delivered Today</p>
        <p class="text-2xl font-bold text-green-400 mt-1">{{ $stats['delivered'] }}</p>
    </div>
    <div class="bg-gray-800 border border-orange-600/40 rounded-lg p-4">
        <p class="text-xs text-gray-400 uppercase tracking-wide">Active Drivers</p>
        <p class="text-2xl font-bold text-orange-400 mt-1">{{ $stats['drivers'] }}</p>
    </div>
</div>

{{-- Quick Actions --}}
<div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-6">
    <a href="{{ route('deliveries.index') }}"
       class="bg-gray-800 border border-gray-700 hover:border-orange-500 rounded-lg p-4 transition group">
        <p class="text-lg font-semibold text-orange-400 group-hover:text-orange-300">🚚 Manage Deliveries</p>
        <p class="text-sm text-gray-400 mt-1">View, assign, and dispatch orders</p>
    </a>
    <a href="{{ route('drivers.index') }}"
       class="bg-gray-800 border border-gray-700 hover:border-orange-500 rounded-lg p-4 transition group">
        <p class="text-lg font-semibold text-orange-400 group-hover:text-orange-300">👤 Manage Drivers</p>
        <p class="text-sm text-gray-400 mt-1">Add and manage driver records</p>
    </a>
    <a href="{{ route('deliveries.schedule') }}"
       class="bg-gray-800 border border-gray-700 hover:border-orange-500 rounded-lg p-4 transition group">
        <p class="text-lg font-semibold text-orange-400 group-hover:text-orange-300">📅 Delivery Schedule</p>
        <p class="text-sm text-gray-400 mt-1">View upcoming scheduled deliveries</p>
    </a>
</div>

{{-- Recent Deliveries Table --}}
<div class="bg-gray-800 border border-gray-700 rounded-lg overflow-hidden">
    <div class="px-5 py-4 border-b border-gray-700 flex items-center justify-between">
        <h3 class="text-base font-semibold text-white">Recent Deliveries</h3>
        <a href="{{ route('deliveries.index') }}" class="text-sm text-orange-400 hover:underline">View all →</a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-700/50 text-gray-400 uppercase text-xs">
                <tr>
                    <th class="px-4 py-3 text-left">Tracking #</th>
                    <th class="px-4 py-3 text-left">Order</th>
                    <th class="px-4 py-3 text-left">Driver</th>
                    <th class="px-4 py-3 text-left">Status</th>
                    <th class="px-4 py-3 text-left">Scheduled</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-700">
                @forelse($recentDeliveries as $delivery)
                <tr class="hover:bg-gray-700/30 transition">
                    <td class="px-4 py-3 font-mono text-gray-300">{{ $delivery->tracking_number ?? '—' }}</td>
                    <td class="px-4 py-3 text-gray-300">{{ $delivery->order?->order_number ?? '—' }}</td>
                    <td class="px-4 py-3 text-gray-300">
                        {{ $delivery->driver ? $delivery->driver->first_name . ' ' . $delivery->driver->last_name : '—' }}
                    </td>
                    <td class="px-4 py-3">
                        @php
                            $colors = [
                                'pending'    => 'bg-yellow-900 text-yellow-300',
                                'assigned'   => 'bg-blue-900 text-blue-300',
                                'dispatched' => 'bg-indigo-900 text-indigo-300',
                                'in_transit' => 'bg-purple-900 text-purple-300',
                                'delivered'  => 'bg-green-900 text-green-300',
                                'failed'     => 'bg-red-900 text-red-300',
                            ];
                            $color = $colors[$delivery->status] ?? 'bg-gray-700 text-gray-300';
                        @endphp
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $color }}">
                            {{ ucfirst(str_replace('_', ' ', $delivery->status)) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-gray-400">
                        {{ $delivery->scheduled_date ? \Carbon\Carbon::parse($delivery->scheduled_date)->format('M d, Y') : '—' }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-4 py-8 text-center text-gray-500">No deliveries found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
