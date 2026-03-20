@extends('department.layouts.app')

@section('title', 'Farm Operations Dashboard')
@section('header', 'Farm Operations Dashboard')

@section('sidebar-links')
    <a href="{{ route('department.farm_operations.dashboard') }}"
       class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium bg-orange-500/20 text-orange-400 border border-orange-500/30">
        🏠 Dashboard
    </a>
    <a href="{{ route('flocks.index') }}"
       class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium text-gray-300 hover:bg-gray-700 hover:text-white">
        🐔 Flock Management
    </a>
    <a href="{{ route('vaccinations.index') }}"
       class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium text-gray-300 hover:bg-gray-700 hover:text-white">
        💉 Vaccinations
    </a>
    <a href="{{ route('vaccinations.upcoming') }}"
       class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium text-gray-300 hover:bg-gray-700 hover:text-white">
        📅 Upcoming Vaccines
    </a>
    <a href="{{ route('supplies.index') }}"
       class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium text-gray-300 hover:bg-gray-700 hover:text-white">
        📦 Supplies
    </a>
@endsection

@section('content')
{{-- Stats Cards --}}
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="bg-gray-800 border border-green-600/40 rounded-lg p-4">
        <p class="text-xs text-gray-400 uppercase tracking-wide">Active Flocks</p>
        <p class="text-2xl font-bold text-green-400 mt-1">{{ $stats['active_flocks'] }}</p>
    </div>
    <div class="bg-gray-800 border border-blue-600/40 rounded-lg p-4">
        <p class="text-xs text-gray-400 uppercase tracking-wide">Total Active Birds</p>
        <p class="text-2xl font-bold text-blue-400 mt-1">{{ number_format($stats['total_birds']) }}</p>
    </div>
    <div class="bg-gray-800 border border-yellow-600/40 rounded-lg p-4">
        <p class="text-xs text-gray-400 uppercase tracking-wide">Vaccines Due (7 days)</p>
        <p class="text-2xl font-bold text-yellow-400 mt-1">{{ $stats['upcoming_vaccinations'] }}</p>
    </div>
</div>

{{-- Quick Actions --}}
<div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-6">
    <a href="{{ route('flocks.index') }}"
       class="bg-gray-800 border border-gray-700 hover:border-orange-500 rounded-lg p-4 transition group">
        <p class="text-lg font-semibold text-orange-400 group-hover:text-orange-300">🐔 Flock Records</p>
        <p class="text-sm text-gray-400 mt-1">Monitor and manage bird batches</p>
    </a>
    <a href="{{ route('vaccinations.index') }}"
       class="bg-gray-800 border border-gray-700 hover:border-orange-500 rounded-lg p-4 transition group">
        <p class="text-lg font-semibold text-orange-400 group-hover:text-orange-300">💉 Vaccination Records</p>
        <p class="text-sm text-gray-400 mt-1">Track health and vaccination history</p>
    </a>
    <a href="{{ route('supplies.index') }}"
       class="bg-gray-800 border border-gray-700 hover:border-orange-500 rounded-lg p-4 transition group">
        <p class="text-lg font-semibold text-orange-400 group-hover:text-orange-300">📦 Supply Inventory</p>
        <p class="text-sm text-gray-400 mt-1">Check and restock farm supplies</p>
    </a>
</div>

{{-- Recent Flocks --}}
<div class="bg-gray-800 border border-gray-700 rounded-lg overflow-hidden">
    <div class="px-5 py-4 border-b border-gray-700 flex items-center justify-between">
        <h3 class="text-base font-semibold text-white">Recent Flocks</h3>
        <a href="{{ route('flocks.index') }}" class="text-sm text-orange-400 hover:underline">View all →</a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-700/50 text-gray-400 uppercase text-xs">
                <tr>
                    <th class="px-4 py-3 text-left">Batch Name</th>
                    <th class="px-4 py-3 text-left">Breed</th>
                    <th class="px-4 py-3 text-left">Count</th>
                    <th class="px-4 py-3 text-left">Status</th>
                    <th class="px-4 py-3 text-left">Added</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-700">
                @forelse($recentFlocks as $flock)
                <tr class="hover:bg-gray-700/30 transition">
                    <td class="px-4 py-3 text-white font-medium">{{ $flock->batch_name }}</td>
                    <td class="px-4 py-3 text-gray-300">{{ ucfirst($flock->breed_type ?? '—') }}</td>
                    <td class="px-4 py-3 text-gray-300">{{ number_format($flock->current_count) }}</td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $flock->status === 'active' ? 'bg-green-900 text-green-300' : 'bg-gray-700 text-gray-300' }}">
                            {{ ucfirst($flock->status) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-gray-400">{{ $flock->created_at->format('M d, Y') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-4 py-8 text-center text-gray-500">No flocks found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
