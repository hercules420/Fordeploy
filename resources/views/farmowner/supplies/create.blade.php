@extends('farmowner.layouts.app')

@section('title', 'Add Supply Item')
@section('header', 'Add Supply Item')
@section('subheader', 'Add new inventory item')

@section('content')
<div class="max-w-2xl">
    <form action="{{ route('supplies.store') }}" method="POST" class="bg-gray-800 border border-gray-700 rounded-lg p-6 space-y-6">
        @csrf
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-300 mb-1">Item Name *</label>
                <input type="text" name="name" value="{{ old('name') }}" required
                    class="w-full px-3 py-2 border border-gray-600 rounded-lg bg-white text-black placeholder-gray-500 focus:ring-2 focus:ring-green-500"
                    placeholder="e.g., Broiler Starter Feed">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Category *</label>
                <select name="category" required class="w-full px-3 py-2 border border-gray-600 rounded-lg bg-white text-black focus:ring-2 focus:ring-green-500">
                    <option value="">Select Category</option>
                    @foreach(['feeds', 'vitamins', 'vaccines', 'medications', 'equipment', 'supplements', 'cleaning', 'packaging', 'other'] as $cat)
                    <option value="{{ $cat }}" {{ old('category') === $cat ? 'selected' : '' }}>{{ ucfirst($cat) }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Supplier</label>
                <select name="supplier_id" class="w-full px-3 py-2 border border-gray-600 rounded-lg bg-white text-black focus:ring-2 focus:ring-green-500">
                    <option value="">No Supplier</option>
                    @foreach($suppliers as $supplier)
                    <option value="{{ $supplier->id }}" {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}>{{ $supplier->company_name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Brand</label>
                <input type="text" name="brand" value="{{ old('brand') }}"
                    class="w-full px-3 py-2 border border-gray-600 rounded-lg bg-white text-black placeholder-gray-500 focus:ring-2 focus:ring-green-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Unit *</label>
                <input type="text" name="unit" value="{{ old('unit', 'kg') }}" required
                    class="w-full px-3 py-2 border border-gray-600 rounded-lg bg-white text-black placeholder-gray-500 focus:ring-2 focus:ring-green-500"
                    placeholder="kg, pcs, liters">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Initial Quantity *</label>
                <input type="number" name="quantity_on_hand" value="{{ old('quantity_on_hand', 0) }}" step="0.01" min="0" required
                    class="w-full px-3 py-2 border border-gray-600 rounded-lg bg-white text-black focus:ring-2 focus:ring-green-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Unit Cost (₱) *</label>
                <input type="number" name="unit_cost" value="{{ old('unit_cost') }}" step="0.01" min="0" required
                    class="w-full px-3 py-2 border border-gray-600 rounded-lg bg-white text-black focus:ring-2 focus:ring-green-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Minimum Stock Level</label>
                <input type="number" name="minimum_stock" value="{{ old('minimum_stock') }}" step="0.01" min="0"
                    class="w-full px-3 py-2 border border-gray-600 rounded-lg bg-white text-black placeholder-gray-500 focus:ring-2 focus:ring-green-500"
                    placeholder="Alert when below this">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Expiration Date</label>
                <input type="date" name="expiration_date" value="{{ old('expiration_date') }}"
                    class="w-full px-3 py-2 border border-gray-600 rounded-lg bg-white text-black focus:ring-2 focus:ring-green-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Storage Location</label>
                <input type="text" name="storage_location" value="{{ old('storage_location') }}"
                    class="w-full px-3 py-2 border border-gray-600 rounded-lg bg-white text-black placeholder-gray-500 focus:ring-2 focus:ring-green-500"
                    placeholder="e.g., Warehouse A, Shelf 3">
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-300 mb-1">Description</label>
                <textarea name="description" rows="2"
                    class="w-full px-3 py-2 border border-gray-600 rounded-lg bg-white text-black placeholder-gray-500 focus:ring-2 focus:ring-green-500">{{ old('description') }}</textarea>
            </div>
        </div>

        <div class="flex gap-4 pt-4">
            <button type="submit" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">Add Item</button>
            <a href="{{ route('supplies.index') }}" class="px-6 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-500">Cancel</a>
        </div>
    </form>
</div>
@endsection
