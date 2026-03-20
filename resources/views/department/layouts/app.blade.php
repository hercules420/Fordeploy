<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Department') - Poultry System</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-900 text-gray-200 min-h-screen flex">

    {{-- Sidebar --}}
    <aside class="w-64 bg-gray-800 border-r border-gray-700 min-h-screen flex flex-col">
        <div class="p-5 border-b border-gray-700">
            <h1 class="text-lg font-bold text-orange-400">🐔 Poultry System</h1>
            <p class="text-xs text-gray-400 mt-1">{{ ucwords(str_replace('_', ' ', Auth::user()->role)) }} Portal</p>
        </div>

        <nav class="flex-1 p-4 space-y-1">
            @yield('sidebar-links')
        </nav>

        <div class="p-4 border-t border-gray-700">
            <p class="text-sm text-gray-300 font-medium truncate">{{ Auth::user()->name }}</p>
            <p class="text-xs text-gray-500 truncate">{{ Auth::user()->email }}</p>
            <form method="POST" action="{{ route('logout') }}" class="mt-3">
                @csrf
                <button type="submit" class="w-full text-left text-sm text-red-400 hover:text-red-300">
                    ⬡ Logout
                </button>
            </form>
        </div>
    </aside>

    {{-- Main Content --}}
    <div class="flex-1 flex flex-col">
        {{-- Top bar --}}
        <header class="bg-gray-800 border-b border-gray-700 px-6 py-4 flex items-center justify-between">
            <h2 class="text-xl font-semibold text-white">@yield('header')</h2>
            <span class="text-sm text-gray-400">{{ now()->format('l, F j, Y') }}</span>
        </header>

        <main class="flex-1 p-6">
            @if(session('success'))
                <div class="mb-4 px-4 py-3 bg-green-800 border border-green-600 text-green-200 rounded-lg text-sm">
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="mb-4 px-4 py-3 bg-red-800 border border-red-600 text-red-200 rounded-lg text-sm">
                    {{ session('error') }}
                </div>
            @endif

            @yield('content')
        </main>
    </div>

</body>
</html>
