<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Support Inbox - Super Admin</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-900 text-gray-200">
    <div class="flex h-screen">
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
                <a href="{{ route('superadmin.users') }}" class="block px-4 py-3 hover:bg-gray-700 rounded-lg">Users</a>
                <a href="{{ route('superadmin.support.index') }}" class="block px-4 py-3 bg-orange-600 text-white rounded-lg">Support</a>
                <hr class="my-4 border-gray-600">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full px-4 py-3 text-left hover:bg-red-600 rounded-lg">Logout</button>
                </form>
            </nav>
        </aside>

        <main class="flex-1 overflow-auto">
            <header class="bg-gray-800 border-b border-gray-700 px-8 py-4">
                <h2 class="text-2xl font-bold">Support Inbox</h2>
                <p class="text-gray-400 text-sm">Farm owner feedback and chat</p>
            </header>

            <div class="p-8">
                @if(session('success'))
                <div class="mb-6 p-4 bg-green-900/50 border border-green-700 rounded-lg text-green-300">{{ session('success') }}</div>
                @endif

                @if(session('error'))
                <div class="mb-6 p-4 bg-red-900/50 border border-red-700 rounded-lg text-red-300">{{ session('error') }}</div>
                @endif

                <div class="bg-gray-800 border border-gray-700 rounded-lg overflow-hidden">
                    @if($tickets->count() > 0)
                    <div class="divide-y divide-gray-700">
                        @foreach($tickets as $ticket)
                        <a href="{{ route('superadmin.support.show', $ticket) }}" class="block px-6 py-4 hover:bg-gray-700/40">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="font-semibold text-white">{{ $ticket->subject }}</p>
                                    <p class="text-sm text-gray-400 mt-1">
                                        Farm: {{ $ticket->farmOwner->farm_name ?? 'N/A' }} · Owner: {{ $ticket->farmOwner->user->name ?? 'N/A' }}
                                    </p>
                                    <p class="text-xs text-gray-500 mt-1">Last update: {{ $ticket->updated_at->format('M d, Y h:i A') }}</p>
                                </div>
                                <span class="px-2 py-1 rounded text-xs font-semibold {{ $ticket->status === 'open' ? 'bg-green-900 text-green-300' : 'bg-gray-700 text-gray-300' }}">{{ ucfirst($ticket->status) }}</span>
                            </div>
                        </a>
                        @endforeach
                    </div>
                    <div class="px-6 py-4 border-t border-gray-700">{{ $tickets->links() }}</div>
                    @else
                    <p class="text-gray-400 px-6 py-10 text-center">No support tickets yet.</p>
                    @endif
                </div>
            </div>
        </main>
    </div>
</body>
</html>
