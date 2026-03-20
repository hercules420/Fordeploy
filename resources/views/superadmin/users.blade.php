<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users - Admin</title>
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
                <a href="{{ route('superadmin.subscriptions') }}" class="block px-4 py-3 hover:bg-gray-700 rounded-lg">Subscriptions</a>
                <a href="{{ route('superadmin.users') }}" class="block px-4 py-3 bg-orange-600 text-white rounded-lg">Users</a>
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
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h2 class="text-2xl font-bold">Users Management</h2>
                        <p class="text-gray-400 text-sm">Manage system users and access</p>
                    </div>
                    <a href="{{ route('hr.users.create') }}" class="px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-lg text-sm font-semibold">
                        + Create Department User
                    </a>
                </div>
            </header>

            <div class="p-8">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div class="bg-gray-800 border border-gray-700 rounded-lg p-4">
                        <p class="text-gray-400 text-xs">Farm Owners</p>
                        <p class="text-2xl font-bold text-blue-400">{{ $farmOwnerUsers->total() }}</p>
                    </div>
                    <div class="bg-gray-800 border border-gray-700 rounded-lg p-4">
                        <p class="text-gray-400 text-xs">Department Users</p>
                        <p class="text-2xl font-bold text-orange-400">{{ $departmentUsers->total() }}</p>
                    </div>
                    <div class="bg-gray-800 border border-gray-700 rounded-lg p-4">
                        <p class="text-gray-400 text-xs">Other Accounts</p>
                        <p class="text-2xl font-bold text-purple-400">{{ $otherUsers->total() }}</p>
                    </div>
                </div>

                <div class="bg-gray-800 rounded-lg border border-gray-700 overflow-hidden mb-8">
                    <div class="px-6 py-4 border-b border-gray-700">
                        <h3 class="font-bold">Farm Owner Accounts</h3>
                        <p class="text-xs text-gray-400">Approval and subscription columns are separated from email verification.</p>
                    </div>
                    <table class="w-full text-sm">
                        <thead class="bg-gray-700 border-b border-gray-600">
                            <tr>
                                <th class="text-left px-6 py-3">Farm</th>
                                <th class="text-left px-6 py-3">Owner</th>
                                <th class="text-left px-6 py-3">Approval</th>
                                <th class="text-left px-6 py-3">Subscription</th>
                                <th class="text-left px-6 py-3">Account Status</th>
                                <th class="text-left px-6 py-3">Joined</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-700">
                            @forelse($farmOwnerUsers as $user)
                            @php
                                $permit = $user->farmOwner?->permit_status;
                                $subStatus = $user->farmOwner?->subscription?->status ?? $user->farmOwner?->subscription_status;
                            @endphp
                            <tr class="hover:bg-gray-700 transition">
                                <td class="px-6 py-4 font-semibold">{{ $user->farmOwner?->farm_name ?? 'N/A' }}</td>
                                <td class="px-6 py-4">
                                    <p class="font-semibold">{{ $user->name }}</p>
                                    <p class="text-xs text-gray-400">{{ $user->email }}</p>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold
                                        @if($permit === 'approved') bg-green-500/20 text-green-400
                                        @elseif($permit === 'pending') bg-yellow-500/20 text-yellow-400
                                        @elseif($permit === 'rejected') bg-red-500/20 text-red-400
                                        @else bg-gray-500/20 text-gray-400
                                        @endif">
                                        {{ ucfirst($permit ?? 'n/a') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold
                                        @if($subStatus === 'active') bg-green-500/20 text-green-400
                                        @elseif($subStatus) bg-yellow-500/20 text-yellow-400
                                        @else bg-gray-500/20 text-gray-400
                                        @endif">
                                        {{ ucfirst((string) ($subStatus ?? 'none')) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold
                                        @if($user->status === 'active') bg-green-500/20 text-green-400
                                        @elseif($user->status === 'inactive') bg-gray-500/20 text-gray-400
                                        @else bg-red-500/20 text-red-400
                                        @endif">
                                        {{ ucfirst($user->status ?? 'active') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-gray-400">{{ $user->created_at?->format('M d, Y') }}</td>
                            </tr>
                            @empty
                            <tr><td class="px-6 py-6 text-gray-400" colspan="6">No farm owner accounts found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                    <div class="px-6 py-4 border-t border-gray-700">
                        {{ $farmOwnerUsers->appends(request()->except('farm_owner_page'))->links('pagination::tailwind') }}
                    </div>
                </div>

                <div class="bg-gray-800 rounded-lg border border-gray-700 overflow-hidden mb-8">
                    <div class="px-6 py-4 border-b border-gray-700">
                        <h3 class="font-bold">Department Users</h3>
                        <p class="text-xs text-gray-400">HR, finance, logistics, sales, farm operations, and admin department accounts.</p>
                    </div>
                    <table class="w-full text-sm">
                        <thead class="bg-gray-700 border-b border-gray-600">
                            <tr>
                                <th class="text-left px-6 py-3">Name</th>
                                <th class="text-left px-6 py-3">Email</th>
                                <th class="text-left px-6 py-3">Department Role</th>
                                <th class="text-left px-6 py-3">Account Status</th>
                                <th class="text-left px-6 py-3">Email Verified</th>
                                <th class="text-left px-6 py-3">Joined</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-700">
                            @forelse($departmentUsers as $user)
                            <tr class="hover:bg-gray-700 transition">
                                <td class="px-6 py-4 font-semibold">{{ $user->name }}</td>
                                <td class="px-6 py-4 text-gray-400">{{ $user->email }}</td>
                                <td class="px-6 py-4">
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-orange-500/20 text-orange-300">
                                        {{ ucfirst(str_replace('_', ' ', $user->role)) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold
                                        @if($user->status === 'active') bg-green-500/20 text-green-400
                                        @elseif($user->status === 'inactive') bg-gray-500/20 text-gray-400
                                        @else bg-red-500/20 text-red-400
                                        @endif">
                                        {{ ucfirst($user->status ?? 'active') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    @if($user->email_verified_at)
                                        <span class="inline-flex items-center px-2 py-1 rounded text-xs font-semibold bg-green-500/20 text-green-400">Verified</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-1 rounded text-xs font-semibold bg-yellow-500/20 text-yellow-400">Pending</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-gray-400">{{ $user->created_at?->format('M d, Y') }}</td>
                            </tr>
                            @empty
                            <tr><td class="px-6 py-6 text-gray-400" colspan="6">No department users found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                    <div class="px-6 py-4 border-t border-gray-700">
                        {{ $departmentUsers->appends(request()->except('department_page'))->links('pagination::tailwind') }}
                    </div>
                </div>

                @if($otherUsers->total() > 0)
                <div class="bg-gray-800 rounded-lg border border-gray-700 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-700">
                        <h3 class="font-bold">Other Accounts</h3>
                        <p class="text-xs text-gray-400">Consumers and super admin accounts kept separate from operational user lists.</p>
                    </div>
                    <table class="w-full text-sm">
                        <thead class="bg-gray-700 border-b border-gray-600">
                            <tr>
                                <th class="text-left px-6 py-3">Name</th>
                                <th class="text-left px-6 py-3">Email</th>
                                <th class="text-left px-6 py-3">Role</th>
                                <th class="text-left px-6 py-3">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-700">
                            @foreach($otherUsers as $user)
                            <tr class="hover:bg-gray-700 transition">
                                <td class="px-6 py-4 font-semibold">{{ $user->name }}</td>
                                <td class="px-6 py-4 text-gray-400">{{ $user->email }}</td>
                                <td class="px-6 py-4">{{ ucfirst(str_replace('_', ' ', $user->role)) }}</td>
                                <td class="px-6 py-4">{{ ucfirst($user->status ?? 'active') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="px-6 py-4 border-t border-gray-700">
                        {{ $otherUsers->appends(request()->except('other_page'))->links('pagination::tailwind') }}
                    </div>
                </div>
                @endif
            </div>
        </main>
    </div>
</body>
</html>
