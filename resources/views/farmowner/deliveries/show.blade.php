@extends('farmowner.layouts.app')

@section('title', $delivery->tracking_number)
@section('header', 'Delivery ' . $delivery->tracking_number)
@section('subheader', optional($delivery->scheduled_date)->format('M d, Y') ?? 'Unscheduled')

@section('header-actions')
<div class="flex gap-2">
    @if($delivery->status === 'preparing')
    <form action="{{ route('deliveries.markPacked', $delivery) }}" method="POST">
        @csrf
        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Mark Packed</button>
    </form>
    @elseif($delivery->status === 'packed' && !$delivery->driver_id)
    <form action="{{ route('deliveries.assign', $delivery) }}" method="POST" class="flex gap-2">
        @csrf
        <select name="driver_id" required class="px-3 py-2 border border-gray-600 rounded-lg">
            <option value="">Assign Driver</option>
            @foreach($availableDrivers ?? [] as $driver)
            <option value="{{ $driver->id }}">{{ $driver->name }}</option>
            @endforeach
        </select>
        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Assign</button>
    </form>
    @elseif($delivery->status === 'assigned' || ($delivery->status === 'packed' && $delivery->driver_id))
    <form action="{{ route('deliveries.dispatch', $delivery) }}" method="POST">
        @csrf
        <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">Out for Delivery</button>
    </form>
    @elseif($delivery->status === 'out_for_delivery')
    <form action="{{ route('deliveries.markDelivered', $delivery) }}" method="POST" class="flex gap-2">
        @csrf
        @if($delivery->cod_amount > 0)
        <input type="number" name="cod_collected" value="{{ $delivery->cod_amount }}" step="0.01" 
            class="w-32 px-3 py-2 border border-gray-600 rounded-lg" placeholder="COD Collected">
        @endif
        <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">✓ Mark Delivered</button>
    </form>
    <form action="{{ route('deliveries.fail', $delivery) }}" method="POST">
        @csrf
        <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700" 
            onclick="return confirm('Mark as failed?')">✗ Failed</button>
    </form>
    @elseif($delivery->status === 'delivered')
    <form action="{{ route('deliveries.markCompleted', $delivery) }}" method="POST">
        @csrf
        <button type="submit" class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700">Mark Completed</button>
    </form>
    @endif
</div>
@endsection

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Status Timeline -->
    <div class="lg:col-span-3">
        <div class="bg-gray-800 border border-gray-700 rounded-lg p-6">
            <div class="flex justify-between items-center">
                @php
                    $steps = ['preparing', 'packed', 'assigned', 'out_for_delivery', 'delivered', 'completed'];
                    $currentIndex = array_search($delivery->status, $steps);
                    if ($delivery->status === 'failed') $currentIndex = -1;
                @endphp
                @foreach($steps as $index => $step)
                <div class="flex flex-col items-center flex-1">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold
                        {{ $index <= $currentIndex ? 'bg-green-600 text-white' : 'bg-gray-200 text-gray-500' }}">
                        @if($index < $currentIndex) ✓ @else {{ $index + 1 }} @endif
                    </div>
                    <p class="text-xs mt-1 {{ $index <= $currentIndex ? 'text-green-600 font-medium' : 'text-gray-500' }}">
                        {{ ucwords(str_replace('_', ' ', $step)) }}
                    </p>
                </div>
                @if($index < count($steps) - 1)
                <div class="flex-1 h-1 {{ $index < $currentIndex ? 'bg-green-600' : 'bg-gray-200' }} mx-2"></div>
                @endif
                @endforeach
            </div>
            @if($delivery->status === 'failed')
            <div class="mt-4 p-3 bg-red-900/30 border border-red-700 rounded-lg">
                <p class="text-red-700 text-sm">❌ Delivery failed. Reason: {{ $delivery->failure_reason ?? 'Not specified' }}</p>
            </div>
            @endif
        </div>
    </div>

    <!-- Customer & Address -->
    <div class="bg-gray-800 border border-gray-700 rounded-lg p-6">
        <h3 class="font-semibold text-lg mb-4">Customer Details</h3>
        <div class="space-y-3">
            <div>
                <p class="text-xs text-gray-400">Name</p>
                <p class="font-medium text-white">{{ $delivery->recipient_name }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400">Phone</p>
                <p class="font-medium text-white">{{ $delivery->recipient_phone }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400">Address</p>
                <p class="text-sm text-gray-300">{{ $delivery->delivery_address }}</p>
            </div>
        </div>
    </div>

    <!-- Driver -->
    <div class="bg-gray-800 border border-gray-700 rounded-lg p-6">
        <h3 class="font-semibold text-lg mb-4">Assigned Driver</h3>
        @if($delivery->driver)
        <div class="space-y-3">
            <div>
                <p class="text-xs text-gray-400">Name</p>
                <p class="font-medium text-white">{{ $delivery->driver->name }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400">Phone</p>
                <p class="font-medium text-white">{{ $delivery->driver->phone }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400">Vehicle</p>
                <span class="px-2 py-1 text-xs bg-blue-900 text-blue-300 rounded-full">
                    {{ ucfirst($delivery->driver->vehicle_type) }} - {{ $delivery->driver->vehicle_plate }}
                </span>
            </div>
        </div>
        @else
        <p class="text-gray-400">No driver assigned</p>
        @endif
    </div>

    <!-- Payment -->
    <div class="bg-gray-800 border border-gray-700 rounded-lg p-6">
        <h3 class="font-semibold text-lg mb-4">Payment</h3>
        <div class="space-y-3">
            @if($delivery->order)
            <div>
                <p class="text-xs text-gray-400">Order #</p>
                <a href="{{ route('orders.show', $delivery->order) }}" class="text-blue-600 hover:underline font-mono">
                    {{ $delivery->order->order_number }}
                </a>
            </div>
            @endif
            <div>
                <p class="text-xs text-gray-400">COD Amount</p>
                <p class="font-medium {{ $delivery->cod_amount > 0 ? 'text-yellow-600' : '' }}">
                    ₱{{ number_format($delivery->cod_amount, 2) }}
                </p>
            </div>
            @if($delivery->cod_amount > 0)
            <div>
                <p class="text-xs text-gray-400">COD Collected</p>
                <p class="font-medium {{ $delivery->cod_collected >= $delivery->cod_amount ? 'text-green-600' : 'text-red-600' }}">
                    ₱{{ number_format($delivery->cod_collected, 2) }}
                </p>
            </div>
            @endif
            <div>
                <p class="text-xs text-gray-400">Delivery Fee</p>
                <p class="font-medium text-white">₱{{ number_format($delivery->delivery_fee, 2) }}</p>
            </div>
        </div>
    </div>

    <!-- Items & Notes -->
    <div class="lg:col-span-3 bg-gray-800 border border-gray-700 rounded-lg p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <h3 class="font-semibold text-lg mb-2">Items</h3>
                <p class="text-gray-300">{{ $delivery->delivery_notes ?? 'No description' }}</p>
            </div>
            <div>
                <h3 class="font-semibold text-lg mb-2">Notes</h3>
                <p class="text-gray-300">{{ $delivery->special_instructions ?? 'No notes' }}</p>
            </div>
        </div>
    </div>

    <!-- Timestamps -->
    <div class="lg:col-span-3 bg-gray-800 border border-gray-700 rounded-lg p-6">
        <h3 class="font-semibold text-lg mb-4">Timeline</h3>
        <div class="flex flex-wrap gap-6 text-sm">
            <div>
                <p class="text-xs text-gray-400">Created</p>
                <p class="font-medium text-white">{{ $delivery->created_at->format('M d, Y g:i A') }}</p>
            </div>
            @if($delivery->dispatched_at)
            <div>
                <p class="text-xs text-gray-400">Dispatched</p>
                <p class="font-medium text-white">{{ $delivery->dispatched_at->format('M d, Y g:i A') }}</p>
            </div>
            @endif
            @if($delivery->delivered_at)
            <div>
                <p class="text-xs text-gray-400">Delivered</p>
                <p class="font-medium text-green-600">{{ $delivery->delivered_at->format('M d, Y g:i A') }}</p>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
