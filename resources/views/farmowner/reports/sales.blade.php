@extends('farmowner.layouts.app')

@section('title', 'Sales Report')
@section('header', 'Sales Report')
@section('subheader', $startDate->format('M d, Y') . ' - ' . $endDate->format('M d, Y'))

@section('content')
<div class="bg-gray-800 border border-gray-700 rounded-lg p-4 mb-6">
    <form method="GET" class="flex flex-wrap gap-4 items-end">
        <div>
            <label class="block text-sm text-gray-400 mb-1">Start Date</label>
            <input type="date" name="start_date" value="{{ $startDate->format('Y-m-d') }}" class="px-3 py-2 bg-gray-700 text-white border border-gray-600 rounded-lg">
        </div>
        <div>
            <label class="block text-sm text-gray-400 mb-1">End Date</label>
            <input type="date" name="end_date" value="{{ $endDate->format('Y-m-d') }}" class="px-3 py-2 bg-gray-700 text-white border border-gray-600 rounded-lg">
        </div>
        <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">Apply</button>
    </form>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
    <div class="bg-gray-800 border border-gray-700 rounded-lg p-5 border-l-4 border-blue-600">
        <p class="text-sm text-gray-300">Total Orders</p>
        <p class="text-2xl font-bold text-blue-500">{{ number_format((float) $totalOrders) }}</p>
    </div>
    <div class="bg-gray-800 border border-gray-700 rounded-lg p-5 border-l-4 border-green-600">
        <p class="text-sm text-gray-300">Completed Sales</p>
        <p class="text-2xl font-bold text-green-500">PHP {{ number_format((float) $totalSales, 2) }}</p>
    </div>
    <div class="bg-gray-800 border border-gray-700 rounded-lg p-5 border-l-4 border-yellow-600">
        <p class="text-sm text-gray-300">Average Order Value</p>
        <p class="text-2xl font-bold text-yellow-500">
            PHP {{ number_format($totalOrders > 0 ? ((float) $totalSales / (float) $totalOrders) : 0, 2) }}
        </p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <div class="bg-gray-800 border border-gray-700 rounded-lg overflow-x-auto">
        <div class="p-5 border-b border-gray-600">
            <h3 class="font-semibold text-lg">Orders By Status</h3>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-gray-700 text-gray-300">
                <tr>
                    <th class="px-4 py-3 text-left">Status</th>
                    <th class="px-4 py-3 text-right">Count</th>
                    <th class="px-4 py-3 text-right">Amount</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-700">
                @forelse($orders as $order)
                    <tr>
                        <td class="px-4 py-3 text-white">{{ ucfirst(str_replace('_', ' ', $order->status)) }}</td>
                        <td class="px-4 py-3 text-right text-gray-300">{{ number_format((float) $order->count) }}</td>
                        <td class="px-4 py-3 text-right text-green-400">PHP {{ number_format((float) $order->total, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-4 py-6 text-center text-gray-400">No orders in selected range.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="bg-gray-800 border border-gray-700 rounded-lg overflow-x-auto">
        <div class="p-5 border-b border-gray-600">
            <h3 class="font-semibold text-lg">Top Customers</h3>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-gray-700 text-gray-300">
                <tr>
                    <th class="px-4 py-3 text-left">Customer</th>
                    <th class="px-4 py-3 text-right">Orders</th>
                    <th class="px-4 py-3 text-right">Total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-700">
                @forelse($topCustomers as $customer)
                    <tr>
                        <td class="px-4 py-3 text-white">{{ $customer->customer_name }}</td>
                        <td class="px-4 py-3 text-right text-gray-300">{{ number_format((float) $customer->order_count) }}</td>
                        <td class="px-4 py-3 text-right text-green-400">PHP {{ number_format((float) $customer->total, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-4 py-6 text-center text-gray-400">No customer sales records found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="bg-gray-800 border border-gray-700 rounded-lg overflow-x-auto">
    <div class="p-5 border-b border-gray-600">
        <h3 class="font-semibold text-lg">Daily Completed Sales Trend</h3>
    </div>
    <table class="w-full text-sm">
        <thead class="bg-gray-700 text-gray-300">
            <tr>
                <th class="px-4 py-3 text-left">Date</th>
                <th class="px-4 py-3 text-right">Amount</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-700">
            @forelse($salesTrend as $point)
                <tr>
                    <td class="px-4 py-3 text-white">{{ \Carbon\Carbon::parse($point->date)->format('M d, Y') }}</td>
                    <td class="px-4 py-3 text-right text-green-400">PHP {{ number_format((float) $point->total, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="2" class="px-4 py-6 text-center text-gray-400">No daily sales trend data found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
