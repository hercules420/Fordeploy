@extends('farmowner.layouts.app')

@section('title', 'Products')
@section('header', 'Product Catalog')
@section('subheader', 'Manage your products for sale')

@section('header-actions')
@php
    $farm_owner = Auth::user()->farmOwner;
    $active_sub = $farm_owner ? $farm_owner->subscriptions()->where('status', 'active')->where('ends_at', '>', now())->first() : null;
    $current_count = $farm_owner ? $farm_owner->products()->count() : 0;
    $can_add = $active_sub && (!$active_sub->product_limit || $current_count < $active_sub->product_limit);
@endphp
@if($active_sub && $can_add)
    <a href="{{ route('products.create') }}" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">+ Add Product</a>
@elseif($active_sub && !$can_add)
    <span class="px-4 py-2 bg-gray-600 text-gray-400 rounded-lg cursor-not-allowed" title="Product limit reached for your {{ ucfirst($active_sub->plan_type) }} plan">+ Add Product (Limit Reached)</span>
@else
    <a href="{{ route('farmowner.subscriptions') }}" class="px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 animate-pulse">🔒 Subscribe to Add Products</a>
@endif
@endsection

@section('content')
<!-- Subscription Alert -->
@if(!$active_sub)
<div class="bg-yellow-900/50 border border-yellow-600 text-yellow-300 p-4 rounded-lg mb-6">
    <div class="flex items-center gap-3">
        <span class="text-2xl">🔒</span>
        <div>
            <p class="font-semibold">Subscription Required</p>
            <p class="text-sm text-yellow-400">You need an active subscription to add products. <a href="{{ route('farmowner.subscriptions') }}" class="underline text-yellow-200 hover:text-white">View subscription plans →</a></p>
        </div>
    </div>
</div>
@elseif($active_sub->product_limit && $current_count >= $active_sub->product_limit)
<div class="bg-red-900/50 border border-red-600 text-red-300 p-4 rounded-lg mb-6">
    <div class="flex items-center gap-3">
        <span class="text-2xl">⚠️</span>
        <div>
            <p class="font-semibold">Product Limit Reached ({{ $current_count }}/{{ $active_sub->product_limit }})</p>
            <p class="text-sm text-red-400">Your {{ ucfirst($active_sub->plan_type) }} plan allows a maximum of {{ $active_sub->product_limit }} products. <a href="{{ route('farmowner.subscriptions') }}" class="underline text-red-200 hover:text-white">Upgrade your plan →</a></p>
        </div>
    </div>
</div>
@endif

@if(session('error'))
<div class="bg-red-900/50 border border-red-600 text-red-300 p-4 rounded-lg mb-6">
    <p class="font-semibold">{{ session('error') }}</p>
</div>
@endif
<!-- Stats -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-gray-800 border border-gray-700 rounded-lg p-4 border-l-4 border-l-blue-600">
        <p class="text-gray-400 text-xs">Total Products</p>
        <p class="text-2xl font-bold text-blue-600">{{ $products->total() }}</p>
    </div>
    <div class="bg-gray-800 border border-gray-700 rounded-lg p-4 border-l-4 border-l-green-600">
        <p class="text-gray-400 text-xs">Active</p>
        <p class="text-2xl font-bold text-green-600">{{ $products->where('status', 'active')->count() }}</p>
    </div>
    <div class="bg-gray-800 border border-gray-700 rounded-lg p-4 border-l-4 border-l-yellow-600">
        <p class="text-gray-400 text-xs">Low Stock</p>
        <p class="text-2xl font-bold text-yellow-600">{{ $products->where('quantity_available', '<=', 20)->count() }}</p>
    </div>
    <div class="bg-gray-800 border border-gray-700 rounded-lg p-4 border-l-4 border-l-purple-600">
        <p class="text-gray-400 text-xs">Out of Stock</p>
        <p class="text-2xl font-bold text-purple-600">{{ $products->where('quantity_available', 0)->count() }}</p>
    </div>
</div>

<!-- Products Table -->
<div class="bg-gray-800 border border-gray-700 rounded-lg">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-700">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Product</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">SKU</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Category</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Price</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Stock</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-600">
                @forelse($products as $product)
                <tr class="hover:bg-gray-700">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            @if($product->image_url)
                            <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="w-10 h-10 object-cover rounded">
                            @else
                            <div class="w-10 h-10 bg-gray-700 rounded flex items-center justify-center text-gray-400">📦</div>
                            @endif
                            <div>
                                <p class="font-medium text-white">{{ $product->name }}</p>
                                <p class="text-xs text-gray-400">{{ $product->unit }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 font-mono text-sm text-gray-300">{{ $product->sku }}</td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 text-xs bg-blue-900 text-blue-300 rounded-full">
                            {{ ucfirst(str_replace('_', ' ', $product->category)) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 font-medium text-white">₱{{ number_format($product->price, 2) }}</td>
                    <td class="px-6 py-4">
                        <span class="{{ $product->quantity_available <= 20 ? 'text-red-400 font-semibold' : 'text-gray-300' }}">
                            {{ $product->quantity_available }}
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 text-xs rounded-full 
                            {{ $product->status === 'active' ? 'bg-green-900 text-green-300' : 'bg-gray-700 text-gray-300' }}">
                            {{ ucfirst($product->status) }}
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex gap-2">
                            <a href="{{ route('products.show', $product) }}" class="text-blue-400 hover:text-blue-300">View</a>
                            <a href="{{ route('products.edit', $product) }}" class="text-green-400 hover:text-green-300">Edit</a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-8 text-center text-gray-400">
                        No products yet. 
                        @if($active_sub && $can_add)
                            <a href="{{ route('products.create') }}" class="text-green-400 hover:underline">Add your first product</a>
                        @else
                            <a href="{{ route('farmowner.subscriptions') }}" class="text-yellow-400 hover:underline">Subscribe to add your first product</a>
                        @endif
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($products->hasPages())
    <div class="p-6 border-t border-gray-600">{{ $products->links() }}</div>
    @endif
</div>
@endsection
