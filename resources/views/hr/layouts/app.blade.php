<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'HR Portal') - Poultry System</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        input[type="text"],
        input[type="email"],
        input[type="password"],
        input[type="number"],
        input[type="date"],
        input[type="datetime-local"],
        input[type="time"],
        input[type="url"],
        input[type="tel"],
        input[type="search"],
        select,
        textarea {
            background-color: white !important;
            color: black !important;
        }

        input::placeholder,
        textarea::placeholder {
            color: #6b7280 !important;
        }

        select option {
            background-color: white;
            color: black;
        }
    </style>
    @stack('styles')
</head>
<body class="bg-gray-900 text-gray-200">
    <div class="flex h-screen">
        @include('hr.partials.sidebar')

        <main class="flex-1 overflow-auto">
            <header class="sticky top-0 z-10 border-b border-gray-700 bg-gray-800">
                <div class="flex items-center justify-between px-8 py-4">
                    <div>
                        <h2 class="text-2xl font-bold text-white">@yield('header', 'HR Workspace')</h2>
                        <p class="text-sm text-gray-400">@yield('subheader', 'Manage hiring, attendance, and payroll preparation.')</p>
                    </div>
                    @yield('header-actions')
                </div>
            </header>

            <div class="p-8">
                @if(session('success'))
                <div class="mb-6 rounded-lg border border-emerald-700 bg-emerald-900/40 p-4 text-emerald-300">
                    {{ session('success') }}
                </div>
                @endif

                @if(session('error'))
                <div class="mb-6 rounded-lg border border-red-700 bg-red-900/40 p-4 text-red-300">
                    {{ session('error') }}
                </div>
                @endif

                @if($errors->any())
                <div class="mb-6 rounded-lg border border-red-700 bg-red-900/40 p-4">
                    <ul class="list-inside list-disc text-sm text-red-300">
                        @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                @yield('content')
            </div>
        </main>
    </div>
    @stack('scripts')
</body>
</html>