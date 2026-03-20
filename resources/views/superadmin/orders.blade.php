<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders - Admin</title>
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
                <a href="{{ route('superadmin.farm_owners') }}" class="block px-4 py-3 hover:bg-gray-700 rounded-lg">Farm Owners</a>
                <a href="{{ route('superadmin.orders') }}" class="block px-4 py-3 bg-orange-600 text-white rounded-lg">Orders</a>
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
                <h2 class="text-2xl font-bold">Orders Management</h2>
                <p class="text-gray-400 text-sm">View and manage all orders</p>
            </header>

            <div class="p-8">
                <!-- Stats -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                    <div class="bg-gray-800 p-4 rounded-lg border border-gray-700">
                        <p class="text-gray-400 text-sm mb-1">Total Orders</p>
                        <p class="text-2xl font-bold text-blue-500">{{ $stats['total_orders'] ?? 0 }}</p>
                    </div>
                    <div class="bg-gray-800 p-4 rounded-lg border border-gray-700">
                        <p class="text-gray-400 text-sm mb-1">Pending Orders</p>
                        <p class="text-2xl font-bold text-yellow-500">{{ $stats['pending_orders'] ?? 0 }}</p>
                    </div>
                    <div class="bg-gray-800 p-4 rounded-lg border border-gray-700">
                        <p class="text-gray-400 text-sm mb-1">Paid Orders</p>
                        <p class="text-2xl font-bold text-green-500">{{ $stats['paid_orders'] ?? 0 }}</p>
                    </div>
                    <div class="bg-gray-800 p-4 rounded-lg border border-gray-700">
                        <p class="text-gray-400 text-sm mb-1">Total Revenue</p>
                        <p class="text-2xl font-bold text-green-600">₱{{ number_format($stats['total_revenue'] ?? 0, 2) }}</p>
                    </div>
                </div>

                <!-- Sales Report Per Farm Owner -->
                @if(isset($sales_per_farm) && $sales_per_farm->count() > 0)
                <div class="bg-gray-800 rounded-lg border border-gray-700 overflow-hidden mb-8">
                    <div class="px-6 py-4 border-b border-gray-700">
                        <h3 class="text-lg font-bold">Sales Report Per Farm Owner</h3>
                        <p class="text-gray-400 text-sm">Revenue breakdown by farm</p>
                    </div>
                    <table class="w-full text-sm">
                        <thead class="bg-gray-700 border-b border-gray-600">
                            <tr>
                                <th class="text-left px-6 py-3">Farm Name</th>
                                <th class="text-left px-6 py-3">Total Orders</th>
                                <th class="text-left px-6 py-3">Total Sales</th>
                                <th class="text-left px-6 py-3">Paid Sales</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-700">
                            @foreach($sales_per_farm as $farm_sale)
                            <tr class="hover:bg-gray-700 transition">
                                <td class="px-6 py-4 font-semibold">{{ $farm_sale->farmOwner?->farm_name ?? 'Unknown' }}</td>
                                <td class="px-6 py-4">{{ $farm_sale->order_count }}</td>
                                <td class="px-6 py-4 font-semibold">₱{{ number_format($farm_sale->total_sales ?? 0, 2) }}</td>
                                <td class="px-6 py-4 font-semibold text-green-400">₱{{ number_format($farm_sale->paid_sales ?? 0, 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif

                @if($orders->count() > 0)
                <div class="bg-gray-800 rounded-lg border border-gray-700 overflow-hidden">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-700 border-b border-gray-600">
                            <tr>
                                <th class="text-left px-6 py-3">Order ID</th>
                                <th class="text-left px-6 py-3">Consumer</th>
                                <th class="text-left px-6 py-3">Farm</th>
                                <th class="text-left px-6 py-3">Total</th>
                                <th class="text-left px-6 py-3">Status</th>
                                <th class="text-left px-6 py-3">Payment</th>
                                <th class="text-left px-6 py-3">Date</th>
                                <th class="text-center px-6 py-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-700">
                            @foreach($orders as $order)
                            <tr class="hover:bg-gray-700 transition">
                                <td class="px-6 py-4 font-mono text-sm">{{ $order->id }}</td>
                                <td class="px-6 py-4">{{ $order->consumer?->name ?? 'Unknown' }}</td>
                                <td class="px-6 py-4">{{ $order->farmOwner?->farm_name ?? 'N/A' }}</td>
                                <td class="px-6 py-4 font-semibold">₱{{ number_format($order->total_amount ?? 0, 2) }}</td>
                                <td class="px-6 py-4">
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold
                                        @if($order->status === 'completed') bg-green-500/20 text-green-400
                                        @elseif($order->status === 'pending') bg-yellow-500/20 text-yellow-400
                                        @else bg-gray-500/20 text-gray-400
                                        @endif">
                                        {{ ucfirst($order->status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold
                                        @if($order->payment_status === 'paid') bg-green-500/20 text-green-400
                                        @else bg-red-500/20 text-red-400
                                        @endif">
                                        {{ ucfirst($order->payment_status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-gray-400">{{ $order->created_at?->format('M d, Y') }}</td>
                                <td class="px-6 py-4 text-center">
                                    <a href="{{ route('orders.show', $order->id) }}" class="px-3 py-1 bg-blue-600 hover:bg-blue-700 rounded text-xs">View</a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="mt-6 flex justify-center">
                    {{ $orders->links('pagination::tailwind') }}
                </div>
                @else
                <div class="text-center py-12">
                    <p class="text-gray-400">No orders found</p>
                </div>
                @endif
            </div>
        </main>
    </div>
</body>
</html>
