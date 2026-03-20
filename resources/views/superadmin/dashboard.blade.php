<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Dashboard</title>
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
                <a href="{{ route('superadmin.dashboard') }}" class="block px-4 py-3 bg-orange-600 text-white rounded-lg">Dashboard</a>
                <a href="{{ route('superadmin.farm_owners') }}" class="block px-4 py-3 hover:bg-gray-700 rounded-lg">Farm Owners</a>
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
                <h2 class="text-2xl font-bold">Dashboard</h2>
                <p class="text-gray-400 text-sm">Welcome, {{ Auth::user()->name }}</p>
            </header>

            <div class="p-8">
                <!-- Statistics Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                    <div class="bg-gray-800 p-6 rounded-lg border border-gray-700">
                        <p class="text-gray-400 text-sm mb-2">Total Users</p>
                        <p class="text-3xl font-bold text-orange-500">{{ $stats['total_users'] }}</p>
                    </div>
                    
                    <div class="bg-gray-800 p-6 rounded-lg border border-gray-700">
                        <p class="text-gray-400 text-sm mb-2">Farm Owners</p>
                        <p class="text-3xl font-bold text-blue-500">{{ $stats['total_farm_owners'] }}</p>
                    </div>
                    
                    <div class="bg-gray-800 p-6 rounded-lg border border-gray-700">
                        <p class="text-gray-400 text-sm mb-2">Pending Verifications</p>
                        <p class="text-3xl font-bold text-red-500">{{ $stats['pending_verifications'] }}</p>
                    </div>
                    
                    <div class="bg-gray-800 p-6 rounded-lg border border-gray-700">
                        <p class="text-gray-400 text-sm mb-2">Active Subscriptions</p>
                        <p class="text-3xl font-bold text-green-500">{{ $stats['active_subscriptions'] }}</p>
                    </div>
                    
                    <div class="bg-gray-800 p-6 rounded-lg border border-gray-700">
                        <p class="text-gray-400 text-sm mb-2">Total Orders</p>
                        <p class="text-3xl font-bold text-purple-500">{{ $stats['total_orders'] }}</p>
                    </div>
                    
                    <div class="bg-gray-800 p-6 rounded-lg border border-gray-700">
                        <p class="text-gray-400 text-sm mb-2">Total Revenue</p>
                        <p class="text-3xl font-bold text-green-600">₱{{ number_format($stats['total_revenue'], 2) }}</p>
                    </div>
                </div>

                <!-- Pending Verifications -->
                @if($pending_farm_owners && $pending_farm_owners->count() > 0)
                <div class="bg-gray-800 rounded-lg border border-gray-700 p-6 mb-8">
                    <h3 class="text-xl font-bold mb-4">Pending Farm Owner Verifications</h3>
                    <table class="w-full text-sm">
                        <thead class="border-b border-gray-600">
                            <tr>
                                <th class="text-left py-2">Farm Name</th>
                                <th class="text-left py-2">Owner</th>
                                <th class="text-left py-2">Date Submitted</th>
                                <th class="text-left py-2">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pending_farm_owners as $farm)
                            <tr class="border-t border-gray-600">
                                <td class="py-3">{{ $farm->farm_name }}</td>
                                <td>{{ $farm->user->name }}</td>
                                <td>{{ $farm->created_at->format('M d, Y') }}</td>
                                <td>
                                    <form method="POST" action="{{ route('superadmin.approve_farm_owner', $farm->id) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="text-green-500 hover:text-green-400 text-sm">Approve</button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif

                <!-- Recent Farm Owners -->
                @if($recent_farm_owners && $recent_farm_owners->count() > 0)
                <div class="bg-gray-800 rounded-lg border border-gray-700 p-6">
                    <h3 class="text-xl font-bold mb-4">Recent Farm Owners</h3>
                    <div class="space-y-3">
                        @foreach($recent_farm_owners as $farm)
                        <div class="flex justify-between items-center py-2 border-b border-gray-600">
                            <div>
                                <p class="font-semibold">{{ $farm->farm_name }}</p>
                                <p class="text-gray-400 text-sm">{{ $farm->user->name }} - {{ $farm->city }}, {{ $farm->province }}</p>
                            </div>
                            <span class="px-3 py-1 text-xs rounded font-semibold
                                @if($farm->permit_status === 'approved') bg-green-600
                                @elseif($farm->permit_status === 'pending') bg-yellow-600
                                @else bg-red-600
                                @endif">
                                {{ ucfirst($farm->permit_status) }}
                            </span>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        </main>
    </div>
</body>
</html>