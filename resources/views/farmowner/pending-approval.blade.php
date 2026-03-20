<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Farm Owner Approval Status</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-900 min-h-screen flex items-center justify-center p-6 text-gray-200">
    <div class="w-full max-w-xl bg-gray-800 border border-gray-700 rounded-2xl p-8 shadow-2xl">
        <h1 class="text-3xl font-bold text-orange-500">Registration Under Review</h1>

        <p class="mt-4 text-gray-300">
            Your farm owner account is currently <span class="font-semibold text-orange-400">{{ $farmOwner?->permit_status ?? 'pending' }}</span>.
        </p>

        <p class="mt-3 text-gray-400">
            You cannot access the farm owner dashboard and modules until the Super Admin approves your registration.
            We will notify you through email once your status is approved or denied.
        </p>

        <div class="mt-8 flex gap-3">
            <a href="{{ route('farmowner.logout') }}"
               onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
               class="px-5 py-3 bg-gray-700 hover:bg-gray-600 rounded-lg font-semibold">
                Logout
            </a>
        </div>

        <form id="logout-form" action="{{ route('farmowner.logout') }}" method="POST" class="hidden">
            @csrf
        </form>
    </div>
</body>
</html>
