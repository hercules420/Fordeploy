<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $farm_owner->farm_name }} - Admin</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-900 text-gray-200">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <aside class="w-64 bg-gray-800 border-r border-gray-700">
            <div class="p-6 border-b border-gray-700">
                <h1 class="text-2xl font-bold text-orange-500">Poultry Admin</h1>
            </div>
            
            <nav class="p-4 space-y-2">
                <a href="{{ route('superadmin.dashboard') }}" class="block px-4 py-3 hover:bg-gray-700 rounded-lg">Dashboard</a>
                <a href="{{ route('superadmin.farm_owners') }}" class="block px-4 py-3 bg-orange-600 text-white rounded-lg">Farm Owners</a>
                <a href="{{ route('superadmin.orders') }}" class="block px-4 py-3 hover:bg-gray-700 rounded-lg">Orders</a>
                <a href="{{ route('superadmin.monitoring') }}" class="block px-4 py-3 hover:bg-gray-700 rounded-lg">Monitoring</a>
                <a href="{{ route('superadmin.subscriptions') }}" class="block px-4 py-3 hover:bg-gray-700 rounded-lg">Subscriptions</a>
                <a href="{{ route('superadmin.users') }}" class="block px-4 py-3 hover:bg-gray-700 rounded-lg">Users</a>
                <a href="{{ route('superadmin.support.index') }}" class="block px-4 py-3 hover:bg-gray-700 rounded-lg">Support</a>
                <hr class="my-4 border-gray-600">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full px-4 py-3 text-left hover:bg-red-600 rounded-lg">Logout</button>
                </form>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 overflow-auto">
            <header class="bg-gray-800 border-b border-gray-700 px-8 py-4">
                <div class="flex items-center gap-4">
                    <a href="{{ route('superadmin.farm_owners') }}" class="text-gray-400 hover:text-white">&larr; Back</a>
                    <div>
                        <h2 class="text-2xl font-bold">{{ $farm_owner->farm_name }}</h2>
                        <p class="text-gray-400 text-sm">Owner: {{ $farm_owner->user?->name }} ({{ $farm_owner->user?->email }})</p>
                    </div>
                </div>
            </header>

            <div class="p-8">
                <!-- Farm Info & Valid ID -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                    <div class="bg-gray-800 p-6 rounded-lg border border-gray-700">
                        <h3 class="text-lg font-bold mb-4">Farm Details</h3>
                        <div class="space-y-3 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-400">Status</span>
                                <span class="px-3 py-1 rounded-full text-xs font-semibold
                                    @if($farm_owner->permit_status === 'approved') bg-green-500/20 text-green-400
                                    @elseif($farm_owner->permit_status === 'pending') bg-yellow-500/20 text-yellow-400
                                    @else bg-red-500/20 text-red-400
                                    @endif">
                                    {{ ucfirst($farm_owner->permit_status) }}
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-400">Address</span>
                                <span>{{ $farm_owner->farm_address }}, {{ $farm_owner->city }}, {{ $farm_owner->province }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-400">Registration #</span>
                                <span>{{ $farm_owner->business_registration_number }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-400">Total Products</span>
                                <span class="font-bold text-blue-400">{{ $farm_owner->products_count }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-400">Total Orders</span>
                                <span class="font-bold text-purple-400">{{ $farm_owner->orders_count }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-400">Total Sales (Paid)</span>
                                <span class="font-bold text-green-400">₱{{ number_format($total_sales, 2) }}</span>
                            </div>
                        </div>

                        @if($farm_owner->permit_status === 'pending')
                        <div class="mt-4 flex gap-2">
                            <form method="POST" action="{{ route('superadmin.approve_farm_owner', $farm_owner->id) }}">
                                @csrf
                                <button type="submit" class="px-4 py-2 bg-green-600 hover:bg-green-700 rounded text-sm font-semibold">Approve</button>
                            </form>
                        </div>
                        @endif
                    </div>

                    <!-- Valid ID -->
                    <div class="bg-gray-800 p-6 rounded-lg border border-gray-700">
                        <h3 class="text-lg font-bold mb-4">Valid ID Verification</h3>
                        @if($farm_owner->valid_id_url)
                        <div class="border border-gray-600 rounded-lg overflow-hidden">
                            <img src="{{ $farm_owner->valid_id_url }}" alt="Valid ID" class="w-full max-h-80 object-contain bg-gray-700">
                        </div>
                        <a href="{{ $farm_owner->valid_id_url }}" target="_blank" class="mt-3 inline-block text-blue-400 hover:text-blue-300 text-sm underline">Open Full Size</a>
                        @else
                        <div class="text-center py-12 text-gray-500">
                            <p class="text-4xl mb-2">🪪</p>
                            <p>No valid ID uploaded</p>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Product Inventory -->
                <div class="bg-gray-800 rounded-lg border border-gray-700 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-700 flex justify-between items-center">
                        <h3 class="text-lg font-bold">Product Inventory</h3>
                        <span class="text-sm text-gray-400">{{ $products->count() }} products</span>
                    </div>

                    @if($products->count() > 0)
                    <table class="w-full text-sm">
                        <thead class="bg-gray-700 border-b border-gray-600">
                            <tr>
                                <th class="text-left px-6 py-3">Product</th>
                                <th class="text-left px-6 py-3">SKU</th>
                                <th class="text-left px-6 py-3">Category</th>
                                <th class="text-left px-6 py-3">Price</th>
                                <th class="text-left px-6 py-3">Stock</th>
                                <th class="text-left px-6 py-3">Sold</th>
                                <th class="text-left px-6 py-3">Status</th>
                                <th class="text-left px-6 py-3">Indicator</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-700">
                            @foreach($products as $product)
                            <tr class="hover:bg-gray-700 transition {{ $product->quantity_available <= 20 ? 'bg-red-900/10' : '' }}">
                                <td class="px-6 py-4 font-semibold">{{ $product->name }}</td>
                                <td class="px-6 py-4 font-mono text-xs">{{ $product->sku }}</td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 text-xs bg-blue-900/50 text-blue-300 rounded-full">
                                        {{ ucfirst(str_replace('_', ' ', $product->category)) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 font-medium">₱{{ number_format($product->price, 2) }}</td>
                                <td class="px-6 py-4 font-bold {{ $product->quantity_available <= 20 ? 'text-red-400' : 'text-white' }}">
                                    {{ $product->quantity_available }}
                                </td>
                                <td class="px-6 py-4 text-gray-400">{{ $product->quantity_sold }}</td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 text-xs rounded-full
                                        @if($product->status === 'active') bg-green-500/20 text-green-400
                                        @elseif($product->status === 'inactive') bg-gray-500/20 text-gray-400
                                        @else bg-red-500/20 text-red-400
                                        @endif">
                                        {{ ucfirst($product->status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    @if($product->quantity_available == 0)
                                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-red-600 text-white animate-pulse">OUT OF STOCK</span>
                                    @elseif($product->quantity_available <= 20)
                                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-yellow-600 text-white">⚠ LOW ON STOCK</span>
                                    @else
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-green-500/20 text-green-400">In Stock</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @else
                    <div class="text-center py-12 text-gray-500">
                        <p>No products found for this farm owner.</p>
                    </div>
                    @endif
                </div>
            </div>
        </main>
    </div>
</body>
</html>
