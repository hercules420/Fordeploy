@extends('farmowner.layouts.app')

@section('title', 'Create Delivery')
@section('header', 'Schedule New Delivery')

@section('content')
<div class="max-w-2xl">
    <form action="{{ route('deliveries.store') }}" method="POST" class="bg-gray-800 border border-gray-700 rounded-lg p-6">
        @csrf
        
        <!-- Order Selection -->
        <h3 class="font-semibold text-lg mb-4 pb-2 border-b border-gray-600">Order Information</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-300 mb-1">Select Order (Optional)</label>
                <select name="order_id"
                    class="w-full px-3 py-2 border border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500">
                    <option value="">-- Manual Entry --</option>
                    @foreach($pendingOrders ?? [] as $order)
                    <option value="{{ $order->id }}" {{ old('order_id') == $order->id ? 'selected' : '' }}>
                        {{ $order->order_number }}
                    </option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- Customer Info -->
        <h3 class="font-semibold text-lg mb-4 pb-2 border-b border-gray-600">Customer Information</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Customer Name *</label>
                <input type="text" name="recipient_name" value="{{ old('recipient_name') }}" required
                    class="w-full px-3 py-2 border border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 @error('recipient_name') border-red-500 @enderror">
                @error('recipient_name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Contact Phone *</label>
                <input type="tel" name="recipient_phone" value="{{ old('recipient_phone') }}" required
                    class="w-full px-3 py-2 border border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500">
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-300 mb-1">Delivery Address *</label>
                <textarea name="delivery_address" rows="2" required
                    class="w-full px-3 py-2 border border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 @error('delivery_address') border-red-500 @enderror">{{ old('delivery_address') }}</textarea>
                @error('delivery_address')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
        </div>

        <!-- Schedule & Assignment -->
        <h3 class="font-semibold text-lg mb-4 pb-2 border-b border-gray-600">Schedule & Assignment</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Scheduled Date *</label>
                <input type="date" name="scheduled_date" value="{{ old('scheduled_date', now()->format('Y-m-d')) }}" required
                    class="w-full px-3 py-2 border border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Assign Driver</label>
                <select name="driver_id"
                    class="w-full px-3 py-2 border border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500">
                    <option value="">-- Assign Later --</option>
                    @foreach($availableDrivers ?? [] as $driver)
                    <option value="{{ $driver->id }}" {{ old('driver_id') == $driver->id ? 'selected' : '' }}>
                        {{ $driver->name }} ({{ ucfirst($driver->vehicle_type) }})
                    </option>
                    @endforeach
                </select>
                <p class="text-xs text-gray-400 mt-1">Logistics staff can assign now or later from the delivery details page.</p>
            </div>
        </div>

        <!-- COD & Items -->
        <h3 class="font-semibold text-lg mb-4 pb-2 border-b border-gray-600">Delivery Details</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">COD Amount</label>
                <input type="number" name="cod_amount" value="{{ old('cod_amount', 0) }}" step="0.01" min="0"
                    class="w-full px-3 py-2 border border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500">
                <p class="text-xs text-gray-500 mt-1">Leave 0 if already paid</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Delivery Fee</label>
                <input type="number" name="delivery_fee" value="{{ old('delivery_fee', 0) }}" step="0.01" min="0"
                    class="w-full px-3 py-2 border border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500">
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-300 mb-1">Items Description</label>
                <textarea name="delivery_notes" rows="2" placeholder="e.g., 50 eggs, 2kg dressed chicken"
                    class="w-full px-3 py-2 border border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500">{{ old('delivery_notes') }}</textarea>
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-300 mb-1">Special Notes</label>
                <textarea name="special_instructions" rows="2" placeholder="Delivery instructions, landmarks..."
                    class="w-full px-3 py-2 border border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500">{{ old('special_instructions') }}</textarea>
            </div>
        </div>

        <div class="flex gap-3">
            <button type="submit" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">Schedule Delivery</button>
            <a href="{{ route('deliveries.index') }}" class="px-6 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-500">Cancel</a>
        </div>
    </form>
</div>
@endsection
