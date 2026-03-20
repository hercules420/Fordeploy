@extends('department.layouts.app')

@section('title', 'Sales Dashboard')
@section('header', 'Sales Dashboard')

@section('sidebar-links')
    <a href="{{ route('department.sales.dashboard') }}"
       class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium bg-orange-500/20 text-orange-400 border border-orange-500/30">
        🏠 Dashboard
    </a>
    <a href="{{ route('orders.index') }}"
       class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium text-gray-300 hover:bg-gray-700 hover:text-white">
        🛒 Orders
    </a>
    <a href="{{ route('products.index') }}"
       class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium text-gray-300 hover:bg-gray-700 hover:text-white">
        🥚 Products
    </a>
    <a href="{{ route('income.index') }}"
       class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium text-gray-300 hover:bg-gray-700 hover:text-white">
        💰 Income
    </a>
@endsection

@section('content')
{{-- Stats Cards --}}
<div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
    <div class="bg-gray-800 border border-gray-700 rounded-lg p-4">
        <p class="text-xs text-gray-400 uppercase tracking-wide">Total Orders</p>
        <p class="text-2xl font-bold text-white mt-1">{{ $stats['total_orders'] }}</p>
    </div>
    <div class="bg-gray-800 border border-yellow-600/40 rounded-lg p-4">
        <p class="text-xs text-gray-400 uppercase tracking-wide">Pending</p>
        <p class="text-2xl font-bold text-yellow-400 mt-1">{{ $stats['pending_orders'] }}</p>
    </div>
    <div class="bg-gray-800 border border-blue-600/40 rounded-lg p-4">
        <p class="text-xs text-gray-400 uppercase tracking-wide">Confirmed</p>
        <p class="text-2xl font-bold text-blue-400 mt-1">{{ $stats['confirmed'] }}</p>
    </div>
    <div class="bg-gray-800 border border-green-600/40 rounded-lg p-4">
        <p class="text-xs text-gray-400 uppercase tracking-wide">Delivered</p>
        <p class="text-2xl font-bold text-green-400 mt-1">{{ $stats['delivered'] }}</p>
    </div>
    <div class="bg-gray-800 border border-orange-600/40 rounded-lg p-4">
        <p class="text-xs text-gray-400 uppercase tracking-wide">Revenue (Paid)</p>
        <p class="text-xl font-bold text-orange-400 mt-1">₱{{ number_format($stats['revenue'], 0) }}</p>
    </div>
</div>

{{-- Quick Actions --}}
<div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-6">
    <a href="{{ route('orders.index') }}"
       class="bg-gray-800 border border-gray-700 hover:border-orange-500 rounded-lg p-4 transition group">
        <p class="text-lg font-semibold text-orange-400 group-hover:text-orange-300">🛒 Manage Orders</p>
        <p class="text-sm text-gray-400 mt-1">View and process customer orders</p>
    </a>
    <a href="{{ route('products.index') }}"
       class="bg-gray-800 border border-gray-700 hover:border-orange-500 rounded-lg p-4 transition group">
        <p class="text-lg font-semibold text-orange-400 group-hover:text-orange-300">🥚 Products</p>
        <p class="text-sm text-gray-400 mt-1">Manage product listings and pricing</p>
    </a>
    <a href="{{ route('income.index') }}"
       class="bg-gray-800 border border-gray-700 hover:border-orange-500 rounded-lg p-4 transition group">
        <p class="text-lg font-semibold text-orange-400 group-hover:text-orange-300">💰 Income Records</p>
        <p class="text-sm text-gray-400 mt-1">View income from completed sales</p>
    </a>
</div>

{{-- Recent Orders --}}
<div class="bg-gray-800 border border-gray-700 rounded-lg overflow-hidden">
    <div class="px-5 py-4 border-b border-gray-700 flex items-center justify-between">
        <h3 class="text-base font-semibold text-white">Recent Orders</h3>
        <a href="{{ route('orders.index') }}" class="text-sm text-orange-400 hover:underline">View all →</a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-700/50 text-gray-400 uppercase text-xs">
                <tr>
                    <th class="px-4 py-3 text-left">Order #</th>
                    <th class="px-4 py-3 text-left">Customer</th>
                    <th class="px-4 py-3 text-left">Total</th>
                    <th class="px-4 py-3 text-left">Status</th>
                    <th class="px-4 py-3 text-left">Payment</th>
                    <th class="px-4 py-3 text-left">Date</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-700">
                @forelse($recentOrders as $order)
                <tr class="hover:bg-gray-700/30 transition">
                    <td class="px-4 py-3 font-mono text-gray-300">{{ $order->order_number }}</td>
                    <td class="px-4 py-3 text-gray-300">{{ $order->consumer?->name ?? '—' }}</td>
                    <td class="px-4 py-3 text-white font-medium">₱{{ number_format($order->total_amount, 2) }}</td>
                    <td class="px-4 py-3">
                        @php
                            $colors = [
                                'pending'    => 'bg-yellow-900 text-yellow-300',
                                'confirmed'  => 'bg-blue-900 text-blue-300',
                                'processing' => 'bg-indigo-900 text-indigo-300',
                                'delivered'  => 'bg-green-900 text-green-300',
                                'cancelled'  => 'bg-red-900 text-red-300',
                            ];
                            $color = $colors[$order->status] ?? 'bg-gray-700 text-gray-300';
                        @endphp
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $color }}">
                            {{ ucfirst($order->status) }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $order->payment_status === 'paid' ? 'bg-green-900 text-green-300' : 'bg-yellow-900 text-yellow-300' }}">
                            {{ ucfirst($order->payment_status) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-gray-400">{{ $order->created_at->format('M d, Y') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-4 py-8 text-center text-gray-500">No orders found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
