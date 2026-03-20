<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Support Ticket - Super Admin</title>
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
                <h2 class="text-2xl font-bold">Support Ticket #{{ $ticket->id }}</h2>
                <p class="text-gray-400 text-sm">{{ $ticket->subject }}</p>
            </header>

            <div class="p-8">
                <div class="mb-4">
                    <a href="{{ route('superadmin.support.index') }}" class="text-orange-400 hover:text-orange-300 text-sm">← Back to Support Inbox</a>
                </div>

                @if(session('success'))
                <div class="mb-6 p-4 bg-green-900/50 border border-green-700 rounded-lg text-green-300">{{ session('success') }}</div>
                @endif

                @if(session('error'))
                <div class="mb-6 p-4 bg-red-900/50 border border-red-700 rounded-lg text-red-300">{{ session('error') }}</div>
                @endif

                <div class="bg-gray-800 border border-gray-700 rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-700 flex items-center justify-between">
                        <div>
                            <p class="font-semibold">Farm: {{ $ticket->farmOwner->farm_name ?? 'N/A' }}</p>
                            <p class="text-sm text-gray-400">Owner: {{ $ticket->farmOwner->user->name ?? 'N/A' }} ({{ $ticket->farmOwner->user->email ?? 'N/A' }})</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="px-2 py-1 rounded text-xs font-semibold {{ $ticket->status === 'open' ? 'bg-green-900 text-green-300' : 'bg-gray-700 text-gray-300' }}">{{ ucfirst($ticket->status) }}</span>
                            @if($ticket->status === 'open')
                            <form method="POST" action="{{ route('superadmin.support.close', $ticket) }}">
                                @csrf
                                <button class="px-3 py-1.5 text-xs bg-red-600 hover:bg-red-700 rounded">Close Ticket</button>
                            </form>
                            @endif
                        </div>
                    </div>

                    <div class="p-6 space-y-4 max-h-[500px] overflow-y-auto bg-gray-900/40">
                        @forelse($ticket->messages as $message)
                        <div class="{{ $message->sender_role === 'superadmin' ? 'text-right' : '' }}">
                            <div class="inline-block max-w-2xl px-4 py-3 rounded-lg {{ $message->sender_role === 'superadmin' ? 'bg-orange-600 text-white' : 'bg-gray-700 text-gray-200' }}">
                                <p class="text-sm font-semibold mb-1">{{ $message->sender->name }} ({{ $message->sender_role === 'superadmin' ? 'You' : 'Farm Owner' }})</p>
                                <p class="text-sm">{{ $message->message }}</p>
                                <p class="text-xs mt-2 opacity-80">{{ $message->created_at->format('M d, Y h:i A') }}</p>
                            </div>
                        </div>
                        @empty
                        <p class="text-gray-400">No messages yet.</p>
                        @endforelse
                    </div>

                    @if($ticket->status === 'open')
                    <form action="{{ route('superadmin.support.reply', $ticket) }}" method="POST" class="p-6 border-t border-gray-700">
                        @csrf
                        <label class="block text-sm text-gray-300 mb-2">Reply to farm owner</label>
                        <textarea name="message" rows="4" required class="w-full rounded-lg bg-gray-900 border border-gray-600 text-gray-200 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500"></textarea>
                        <div class="mt-3 text-right">
                            <button type="submit" class="px-5 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-lg font-semibold">Send Reply</button>
                        </div>
                    </form>
                    @else
                    <div class="p-6 border-t border-gray-700 text-sm text-gray-400">This ticket is closed. Replies are disabled.</div>
                    @endif
                </div>
            </div>
        </main>
    </div>
</body>
</html>
