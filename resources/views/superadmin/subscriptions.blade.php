<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscriptions - Admin</title>
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
                <a href="{{ route('superadmin.orders') }}" class="block px-4 py-3 hover:bg-gray-700 rounded-lg">Orders</a>
                <a href="{{ route('superadmin.monitoring') }}" class="block px-4 py-3 hover:bg-gray-700 rounded-lg">Monitoring</a>
                <a href="{{ route('superadmin.subscriptions') }}" class="block px-4 py-3 bg-orange-600 text-white rounded-lg">Subscriptions</a>
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
                <h2 class="text-2xl font-bold">Subscriptions</h2>
                <p class="text-gray-400 text-sm">Monitor farm owner subscriptions</p>
            </header>

            <div class="p-8">
                @if($subscriptions->count() > 0)
                <div class="bg-gray-800 rounded-lg border border-gray-700 overflow-hidden">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-700 border-b border-gray-600">
                            <tr>
                                <th class="text-left px-6 py-3">Farm</th>
                                <th class="text-left px-6 py-3">Plan</th>
                                <th class="text-left px-6 py-3">Status</th>
                                <th class="text-left px-6 py-3">Started</th>
                                <th class="text-left px-6 py-3">Expires</th>
                                <th class="text-left px-6 py-3">Payment Ref</th>
                                <th class="text-center px-6 py-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-700">
                            @foreach($subscriptions as $sub)
                            <tr class="hover:bg-gray-700 transition">
                                <td class="px-6 py-4 font-semibold">{{ $sub->farmOwner?->farm_name ?? 'N/A' }}</td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 rounded text-xs bg-blue-500/20 text-blue-400">
                                        {{ ucfirst($sub->plan_type ?? 'standard') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold
                                        @if($sub->status === 'active') bg-green-500/20 text-green-400
                                        @elseif($sub->status === 'expiring') bg-yellow-500/20 text-yellow-400
                                        @else bg-red-500/20 text-red-400
                                        @endif">
                                        {{ ucfirst($sub->status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-gray-400">{{ $sub->started_at?->format('M d, Y') }}</td>
                                <td class="px-6 py-4 text-gray-400">{{ $sub->ends_at?->format('M d, Y') }}</td>
                                <td class="px-6 py-4 font-mono text-xs">{{ $sub->paymongo_subscription_id ?? '-' }}</td>
                                <td class="px-6 py-4 text-center">
                                    <a href="#" class="px-3 py-1 bg-blue-600 hover:bg-blue-700 rounded text-xs">Details</a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="mt-6 flex justify-center">
                    {{ $subscriptions->links('pagination::tailwind') }}
                </div>
                @else
                <div class="text-center py-12">
                    <p class="text-gray-400">No subscriptions found</p>
                </div>
                @endif
            </div>
        </main>
    </div>
</body>
</html>
