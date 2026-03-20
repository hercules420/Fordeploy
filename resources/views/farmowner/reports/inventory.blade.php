@extends('farmowner.layouts.app')

@section('title', 'Inventory Report')
@section('header', 'Inventory Report')
@section('subheader', 'Stock levels, value, and critical alerts')

@section('header-actions')
<a href="{{ route('reports.export', ['type' => 'inventory']) }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Export CSV</a>
@endsection

@section('content')
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
    <div class="bg-gray-800 border border-gray-700 rounded-lg p-5 border-l-4 border-blue-600">
        <p class="text-sm text-gray-300">Total Stock Value</p>
        <p class="text-2xl font-bold text-blue-500">PHP {{ number_format((float) $totalValue, 2) }}</p>
    </div>
    <div class="bg-gray-800 border border-gray-700 rounded-lg p-5 border-l-4 border-yellow-600">
        <p class="text-sm text-gray-300">Low Stock</p>
        <p class="text-2xl font-bold text-yellow-500">{{ $lowStock }}</p>
    </div>
    <div class="bg-gray-800 border border-gray-700 rounded-lg p-5 border-l-4 border-red-600">
        <p class="text-sm text-gray-300">Out Of Stock</p>
        <p class="text-2xl font-bold text-red-500">{{ $outOfStock }}</p>
    </div>
    <div class="bg-gray-800 border border-gray-700 rounded-lg p-5 border-l-4 border-orange-600">
        <p class="text-sm text-gray-300">Expiring In 30 Days</p>
        <p class="text-2xl font-bold text-orange-500">{{ $expiringSoon }}</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <div class="bg-gray-800 border border-gray-700 rounded-lg">
        <div class="p-5 border-b border-gray-600">
            <h3 class="font-semibold text-lg">Value By Category</h3>
        </div>
        <div class="p-5 space-y-3">
            @forelse($stockValue as $row)
                <div class="flex justify-between border-b border-gray-700 pb-2">
                    <span class="text-gray-300">{{ ucfirst($row->category) }}</span>
                    <span class="font-semibold text-blue-400">PHP {{ number_format((float) $row->value, 2) }}</span>
                </div>
            @empty
                <p class="text-gray-400">No stock value data available.</p>
            @endforelse
        </div>
    </div>

    <div class="bg-gray-800 border border-gray-700 rounded-lg">
        <div class="p-5 border-b border-gray-600">
            <h3 class="font-semibold text-lg">Quick Notes</h3>
        </div>
        <div class="p-5 text-sm text-gray-300 space-y-2">
            <p>Keep low stock items above minimum to avoid production disruptions.</p>
            <p>Review expiring items weekly and prioritize usage or rotation.</p>
            <p>Use this report with purchase planning before high-demand periods.</p>
        </div>
    </div>
</div>

<div class="bg-gray-800 border border-gray-700 rounded-lg overflow-x-auto">
    <div class="p-5 border-b border-gray-600">
        <h3 class="font-semibold text-lg">Supply Items</h3>
    </div>
    <table class="w-full text-sm">
        <thead class="bg-gray-700 text-gray-300">
            <tr>
                <th class="px-4 py-3 text-left">Item</th>
                <th class="px-4 py-3 text-left">Category</th>
                <th class="px-4 py-3 text-right">Qty</th>
                <th class="px-4 py-3 text-right">Min</th>
                <th class="px-4 py-3 text-right">Unit Cost</th>
                <th class="px-4 py-3 text-right">Value</th>
                <th class="px-4 py-3 text-left">Status</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-700">
            @forelse($supplies as $item)
                @php
                    $value = (float) $item->quantity_on_hand * (float) $item->unit_cost;
                @endphp
                <tr>
                    <td class="px-4 py-3 text-white">{{ $item->name }}</td>
                    <td class="px-4 py-3 text-gray-300">{{ ucfirst($item->category) }}</td>
                    <td class="px-4 py-3 text-right">{{ number_format((float) $item->quantity_on_hand, 2) }}</td>
                    <td class="px-4 py-3 text-right">{{ number_format((float) $item->minimum_stock, 2) }}</td>
                    <td class="px-4 py-3 text-right">PHP {{ number_format((float) $item->unit_cost, 2) }}</td>
                    <td class="px-4 py-3 text-right">PHP {{ number_format($value, 2) }}</td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-1 rounded text-xs {{ $item->status === 'out_of_stock' ? 'bg-red-900 text-red-300' : ($item->status === 'low_stock' ? 'bg-yellow-900 text-yellow-300' : 'bg-green-900 text-green-300') }}">
                            {{ ucfirst(str_replace('_', ' ', $item->status)) }}
                        </span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-4 py-6 text-center text-gray-400">No supply items found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
