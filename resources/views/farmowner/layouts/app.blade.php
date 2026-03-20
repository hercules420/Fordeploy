<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Farm Portal') - Poultry System</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        /* Global form input styling for better visibility */
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
        @include('farmowner.partials.sidebar')

        <main class="flex-1 overflow-auto">
            <header class="bg-gray-800 border-b border-gray-700 sticky top-0 z-10">
                <div class="px-8 py-4 flex justify-between items-center">
                    <div>
                        <h2 class="text-2xl font-bold text-white">@yield('header', 'Dashboard')</h2>
                        <p class="text-gray-400 text-sm">@yield('subheader', 'Welcome back, ' . Auth::user()->name)</p>
                    </div>
                    @yield('header-actions')
                </div>
            </header>

            <div class="p-8">
                @if(session('success'))
                <div class="mb-6 p-4 bg-green-900/50 border border-green-700 rounded-lg text-green-400">
                    {{ session('success') }}
                </div>
                @endif

                @if(session('error'))
                <div class="mb-6 p-4 bg-red-900/50 border border-red-700 rounded-lg text-red-400">
                    {{ session('error') }}
                </div>
                @endif

                @if($errors->any())
                <div class="mb-6 p-4 bg-red-900/50 border border-red-700 rounded-lg">
                    <ul class="text-red-400 text-sm list-disc list-inside">
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
    <script>
        // Preserve scroll position in sidebar when navigating
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.querySelector('aside nav');
            const mainContent = document.querySelector('main');
            
            // Restore scroll positions on page load
            if (sidebar) {
                const savedSidebarScroll = sessionStorage.getItem('sidebarScrollPos');
                if (savedSidebarScroll) {
                    sidebar.scrollTop = parseInt(savedSidebarScroll);
                }
            }
            
            if (mainContent) {
                const savedMainScroll = sessionStorage.getItem('mainScrollPos');
                if (savedMainScroll) {
                    mainContent.scrollTop = parseInt(savedMainScroll);
                }
            }
            
            // Save scroll positions on navigation
            const sidebarLinks = document.querySelectorAll('aside nav a');
            sidebarLinks.forEach(link => {
                link.addEventListener('click', function() {
                    if (sidebar) {
                        sessionStorage.setItem('sidebarScrollPos', sidebar.scrollTop);
                    }
                    if (mainContent) {
                        sessionStorage.setItem('mainScrollPos', mainContent.scrollTop);
                    }
                });
            });
        });
    </script>
    @stack('scripts')
</body>
</html>
