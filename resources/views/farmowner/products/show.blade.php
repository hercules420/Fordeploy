@extends('farmowner.layouts.app')

@section('title', $product->name)
@section('header', $product->name)
@section('subheader', $product->sku . ' • ' . ucfirst(str_replace('_', ' ', $product->category)))

@section('header-actions')
<div class="flex gap-2">
    <a href="{{ route('products.edit', $product) }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Edit</a>
    <span class="px-4 py-2 rounded-lg {{ $product->status === 'active' ? 'bg-green-900 text-green-300' : 'bg-gray-700 text-gray-300' }}">
        {{ ucfirst($product->status) }}
    </span>
</div>
@endsection

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Product Image & Basic Info -->
    <div class="space-y-4">
        <div class="bg-gray-800 border border-gray-700 rounded-lg p-6">
            @if($product->image_url)
            <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="w-full h-48 object-cover rounded-lg mb-4">
            @else
            <div class="w-full h-48 bg-gray-700 rounded-lg flex items-center justify-center text-gray-400 text-4xl mb-4">📦</div>
            @endif
            
            <div class="space-y-3">
                <div>
                    <p class="text-xs text-gray-400">Category</p>
                    <span class="px-2 py-1 text-xs bg-blue-900 text-blue-300 rounded-full">
                        {{ ucfirst(str_replace('_', ' ', $product->category)) }}
                    </span>
                </div>
                <div>
                    <p class="text-xs text-gray-400">Unit</p>
                    <p class="font-medium text-white">{{ $product->unit }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400">Minimum Order</p>
                    <p class="font-medium text-white">{{ $product->minimum_order }} {{ $product->unit }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Pricing & Stock -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Pricing Card -->
        <div class="bg-gray-800 border border-gray-700 rounded-lg p-6">
            <h3 class="font-semibold text-lg mb-4 text-white">Pricing & Inventory</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="text-center p-4 bg-green-900/50 rounded-lg">
                    <p class="text-2xl font-bold text-green-400">₱{{ number_format($product->price, 2) }}</p>
                    <p class="text-xs text-gray-300">Selling Price</p>
                </div>
                <div class="text-center p-4 bg-gray-700 rounded-lg">
                    <p class="text-2xl font-bold text-gray-300">₱{{ number_format($product->cost_price ?? 0, 2) }}</p>
                    <p class="text-xs text-gray-300">Cost Price</p>
                </div>
                <div class="text-center p-4 {{ $product->quantity_available <= 20 ? 'bg-red-900/50' : 'bg-blue-900/50' }} rounded-lg">
                    <p class="text-2xl font-bold {{ $product->quantity_available <= 20 ? 'text-red-400' : 'text-blue-400' }}">
                        {{ $product->quantity_available }}
                    </p>
                    <p class="text-xs text-gray-300">In Stock</p>
                </div>
                <div class="text-center p-4 bg-yellow-900/50 rounded-lg">
                    <p class="text-2xl font-bold text-yellow-400">{{ $product->discount_percentage ?? 0 }}%</p>
                    <p class="text-xs text-gray-300">Discount</p>
                </div>
            </div>
            @if($product->cost_price && $product->price > $product->cost_price)
            <div class="mt-4 p-3 bg-green-900/30 rounded-lg">
                <p class="text-sm text-green-400">
                    Profit Margin: <span class="font-bold">₱{{ number_format($product->price - $product->cost_price, 2) }}</span> 
                    ({{ number_format((($product->price - $product->cost_price) / $product->price) * 100, 1) }}%)
                </p>
            </div>
            @endif
        </div>

        <!-- Description -->
        @if($product->description)
        <div class="bg-gray-800 border border-gray-700 rounded-lg p-6">
            <h3 class="font-semibold text-lg mb-2 text-white">Description</h3>
            <p class="text-gray-300">{{ $product->description }}</p>
        </div>
        @endif

        <!-- Quick Stock Update -->
        <div class="bg-gray-800 border border-gray-700 rounded-lg p-6">
            <h3 class="font-semibold text-lg mb-4 text-white">Quick Stock Update</h3>
            <form action="{{ route('products.update-stock', $product) }}" method="POST" class="flex gap-4">
                @csrf
                @method('PATCH')
                <div class="flex-1">
                    <input type="number" name="quantity" placeholder="Quantity to add/subtract" 
                        class="w-full px-3 py-2 bg-gray-700 border border-gray-600 text-white placeholder-gray-400 rounded-lg focus:ring-2 focus:ring-green-500">
                </div>
                <button type="submit" name="action" value="add" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">+ Add</button>
                <button type="submit" name="action" value="subtract" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">- Subtract</button>
            </form>
        </div>

        <!-- Meta Info -->
        <div class="bg-gray-800 border border-gray-700 rounded-lg p-6">
            <h3 class="font-semibold text-lg mb-4 text-white">Product Details</h3>
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div>
                    <p class="text-gray-400">Created</p>
                    <p class="font-medium text-white">{{ $product->created_at->format('M d, Y g:i A') }}</p>
                </div>
                <div>
                    <p class="text-gray-400">Last Updated</p>
                    <p class="font-medium text-white">{{ $product->updated_at->format('M d, Y g:i A') }}</p>
                </div>
                @if($product->published_at)
                <div>
                    <p class="text-gray-400">Published</p>
                    <p class="font-medium text-white">{{ $product->published_at->format('M d, Y') }}</p>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
