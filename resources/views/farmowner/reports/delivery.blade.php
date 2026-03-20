@extends('farmowner.layouts.app')

@section('title', 'Delivery Report')
@section('header', 'Delivery Report')
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

<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
    <div class="bg-gray-800 border border-gray-700 rounded-lg p-5 border-l-4 border-blue-600">
        <p class="text-sm text-gray-300">Total Deliveries</p>
        <p class="text-2xl font-bold text-blue-500">{{ number_format((float) $totalDeliveries) }}</p>
    </div>
    <div class="bg-gray-800 border border-gray-700 rounded-lg p-5 border-l-4 border-green-600">
        <p class="text-sm text-gray-300">Completed</p>
        <p class="text-2xl font-bold text-green-500">{{ number_format((float) $completedDeliveries) }}</p>
    </div>
    <div class="bg-gray-800 border border-gray-700 rounded-lg p-5 border-l-4 border-yellow-600">
        <p class="text-sm text-gray-300">Success Rate</p>
        <p class="text-2xl font-bold text-yellow-500">{{ number_format((float) $successRate, 1) }}%</p>
    </div>
    <div class="bg-gray-800 border border-gray-700 rounded-lg p-5 border-l-4 border-indigo-600">
        <p class="text-sm text-gray-300">COD Collected</p>
        <p class="text-2xl font-bold text-indigo-500">PHP {{ number_format((float) ($codStats->collected_cod ?? 0), 2) }}</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <div class="bg-gray-800 border border-gray-700 rounded-lg overflow-x-auto">
        <div class="p-5 border-b border-gray-600">
            <h3 class="font-semibold text-lg">Delivery Status Breakdown</h3>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-gray-700 text-gray-300">
                <tr>
                    <th class="px-4 py-3 text-left">Status</th>
                    <th class="px-4 py-3 text-right">Count</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-700">
                @forelse($deliveryStats as $row)
                    <tr>
                        <td class="px-4 py-3 text-white">{{ ucfirst(str_replace('_', ' ', $row->status)) }}</td>
                        <td class="px-4 py-3 text-right text-gray-300">{{ number_format((float) $row->count) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2" class="px-4 py-6 text-center text-gray-400">No delivery records in selected range.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="bg-gray-800 border border-gray-700 rounded-lg overflow-x-auto">
        <div class="p-5 border-b border-gray-600">
            <h3 class="font-semibold text-lg">Driver Performance</h3>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-gray-700 text-gray-300">
                <tr>
                    <th class="px-4 py-3 text-left">Driver</th>
                    <th class="px-4 py-3 text-right">Total</th>
                    <th class="px-4 py-3 text-right">Completed</th>
                    <th class="px-4 py-3 text-right">Success</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-700">
                @forelse($driverPerformance as $row)
                    <tr>
                        <td class="px-4 py-3 text-white">{{ $row->driver->name ?? 'Unknown Driver' }}</td>
                        <td class="px-4 py-3 text-right text-gray-300">{{ number_format((float) $row->total) }}</td>
                        <td class="px-4 py-3 text-right text-gray-300">{{ number_format((float) $row->completed) }}</td>
                        <td class="px-4 py-3 text-right text-green-400">{{ number_format((float) $row->success_rate, 1) }}%</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-6 text-center text-gray-400">No driver performance data available.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="bg-gray-800 border border-gray-700 rounded-lg p-5">
    <h3 class="font-semibold text-lg mb-3">COD Snapshot</h3>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
        <div class="p-4 bg-gray-700 rounded">
            <p class="text-gray-300">Total COD Amount</p>
            <p class="text-xl font-bold text-white">PHP {{ number_format((float) ($codStats->total_cod ?? 0), 2) }}</p>
        </div>
        <div class="p-4 bg-gray-700 rounded">
            <p class="text-gray-300">Remaining COD</p>
            <p class="text-xl font-bold text-orange-400">
                PHP {{ number_format((float) (($codStats->total_cod ?? 0) - ($codStats->collected_cod ?? 0)), 2) }}
            </p>
        </div>
    </div>
</div>
@endsection
