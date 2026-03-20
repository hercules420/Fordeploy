<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Marketplace</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-900 text-gray-100">
    <div class="mx-auto max-w-4xl px-4 py-6">
        @include('marketplace.partials.navbar')

        <div class="rounded-2xl border border-gray-700 bg-gray-800 p-6 shadow-sm">
            <h1 class="text-2xl font-extrabold">My Profile</h1>
            <p class="mt-1 text-sm text-gray-400">Update your name, contact number, and location.</p>

            @if(session('success'))
                <div class="mt-4 rounded-lg border border-green-600/40 bg-green-900/30 p-3 text-green-200">{{ session('success') }}</div>
            @endif

            <form method="POST" action="{{ route('marketplace.profile.update') }}" class="mt-5 space-y-4">
                @csrf
                @method('PATCH')

                <div>
                    <label class="mb-1 block text-sm text-gray-300">Name</label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" required class="w-full rounded-lg border border-gray-600 bg-white px-3 py-2 text-black">
                    @error('name')<p class="mt-1 text-xs text-red-400">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm text-gray-300">Contact Number</label>
                    <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" class="w-full rounded-lg border border-gray-600 bg-white px-3 py-2 text-black">
                    @error('phone')<p class="mt-1 text-xs text-red-400">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm text-gray-300">Location</label>
                    <input type="text" name="location" value="{{ old('location', $user->location) }}" placeholder="City / Province" class="w-full rounded-lg border border-gray-600 bg-white px-3 py-2 text-black">
                    @error('location')<p class="mt-1 text-xs text-red-400">{{ $message }}</p>@enderror
                </div>

                <button type="submit" class="rounded-lg bg-orange-600 px-5 py-2 font-semibold text-white hover:bg-orange-700">Save Profile</button>
            </form>

            <div class="mt-8 border-t border-gray-700 pt-5">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-400">Session</h2>
                <p class="mt-1 text-sm text-gray-400">Sign out of your marketplace account on this browser.</p>

                <form method="POST" action="{{ route('logout') }}" class="mt-3">
                    @csrf
                    <button
                        type="submit"
                        class="rounded-lg border border-red-500/60 bg-red-900/30 px-5 py-2 font-semibold text-red-200 hover:bg-red-800/40"
                    >
                        Log Out
                    </button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
