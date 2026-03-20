@extends('farmowner.layouts.app')

@section('title', 'Production Report')
@section('header', 'Production Report')
@section('subheader', $startDate->format('M d, Y') . ' - ' . $endDate->format('M d, Y'))

@section('header-actions')
<a href="{{ route('reports.export', ['type' => 'production']) }}?start_date={{ $startDate->format('Y-m-d') }}&end_date={{ $endDate->format('Y-m-d') }}" 
   class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">📥 Export CSV</a>
@endsection

@section('content')
<!-- Date Filter -->
<div class="bg-gray-800 border border-gray-700 rounded-lg p-4 mb-6">
    <form method="GET" class="flex flex-wrap gap-4 items-end">
        <div>
            <label class="block text-sm text-gray-600 mb-1">Start Date</label>
            <input type="date" name="start_date" value="{{ $startDate->format('Y-m-d') }}"
                class="px-3 py-2 bg-gray-700 text-white border border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500">
        </div>
        <div>
            <label class="block text-sm text-gray-600 mb-1">End Date</label>
            <input type="date" name="end_date" value="{{ $endDate->format('Y-m-d') }}"
                class="px-3 py-2 bg-gray-700 text-white border border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500">
        </div>
        <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">Apply</button>
    </form>
</div>

<!-- Production Summary -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
    <div class="bg-gray-800 border border-gray-700 rounded-lg p-6 text-center border-l-4 border-blue-600">
        <p class="text-3xl font-bold text-blue-600">{{ number_format($flocks->sum('current_count')) }}</p>
        <p class="text-gray-300 text-sm">Total Birds</p>
    </div>
    <div class="bg-gray-800 border border-gray-700 rounded-lg p-6 text-center border-l-4 border-orange-500">
        <p class="text-3xl font-bold text-orange-600">{{ number_format((float) ($eggProduction->collected ?? 0)) }}</p>
        <p class="text-gray-300 text-sm">Eggs Collected</p>
    </div>
    <div class="bg-gray-800 border border-gray-700 rounded-lg p-6 text-center border-l-4 border-red-600">
        <p class="text-3xl font-bold text-red-600">{{ number_format((float) ($mortalityStats->total_mortality ?? 0)) }}</p>
        <p class="text-gray-300 text-sm">Mortality (Period)</p>
    </div>
    <div class="bg-gray-800 border border-gray-700 rounded-lg p-6 text-center border-l-4 border-green-600">
        <p class="text-3xl font-bold text-green-600">{{ number_format((float) ($feedConsumption->total_feed ?? 0), 1) }}</p>
        <p class="text-gray-300 text-sm">Feed Used (kg)</p>
    </div>
</div>

<!-- Flock Details -->
<div class="bg-gray-800 border border-gray-700 rounded-lg mb-6">
    <div class="p-6 border-b border-gray-600">
        <h3 class="font-semibold text-lg">Active Flocks Performance</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-700">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400">Flock</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400">Type</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400">Birds</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400">Age (Days)</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400">Mortality</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400">Eggs (Period)</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400">Feed Used (kg)</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-600">
                @forelse($flocks as $flock)
                <tr>
                    <td class="px-6 py-4 font-medium text-white">
                        <a href="{{ route('flocks.show', $flock) }}" class="text-blue-600 hover:underline">{{ $flock->batch_name }}</a>
                    </td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 text-xs rounded-full 
                            {{ $flock->flock_type === 'layer' ? 'bg-yellow-900 text-yellow-300' : 'bg-orange-900 text-orange-300' }}">
                            {{ ucfirst($flock->flock_type) }}
                        </span>
                    </td>
                    <td class="px-6 py-4">{{ number_format($flock->current_count) }}</td>
                    <td class="px-6 py-4 text-gray-300">-</td>
                    <td class="px-6 py-4 {{ $flock->mortality_rate > 3 ? 'text-red-600 font-semibold' : 'text-gray-600' }}">
                        {{ number_format($flock->mortality_rate, 2) }}%
                    </td>
                    <td class="px-6 py-4 text-gray-300">-</td>
                    <td class="px-6 py-4 text-gray-300">-</td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-8 text-center text-gray-400">No active flocks</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Daily Production Trend -->
<div class="bg-gray-800 border border-gray-700 rounded-lg">
    <div class="p-6 border-b border-gray-600">
        <h3 class="font-semibold text-lg">Daily Egg Production (Last 14 Days)</h3>
    </div>
    <div class="p-6">
        <div class="flex items-end gap-1 h-32">
            @foreach($eggTrend->take(14) as $day)
            @php
                $max = $eggTrend->max('eggs') ?: 1;
                $height = ($day->eggs / $max) * 100;
            @endphp
            <div class="flex-1 flex flex-col items-center">
                <div class="w-full bg-yellow-900/300 rounded-t" style="height: {{ $height }}%"></div>
                <p class="text-xs text-gray-500 mt-1 rotate-45 origin-left">{{ \Carbon\Carbon::parse($day->date)->format('d') }}</p>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
