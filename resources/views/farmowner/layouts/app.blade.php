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
                    <p>{{ session('success') }}</p>

                    @if(session('verification_url'))
                    <div class="mt-3 flex flex-wrap items-center gap-2">
                        <a
                            href="{{ session('verification_url') }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center rounded bg-green-700 px-3 py-2 text-xs font-semibold text-white hover:bg-green-600"
                        >
                            Open Verification Link
                        </a>
                        <button
                            type="button"
                            class="inline-flex items-center rounded bg-gray-700 px-3 py-2 text-xs font-semibold text-white hover:bg-gray-600"
                            data-copy-target="verification-link"
                        >
                            Copy Verification Link
                        </button>
                        <input type="hidden" id="verification-link" value="{{ session('verification_url') }}">
                    </div>
                    @endif
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
        // Preserve scroll position in sidebar when navigating.
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.querySelector('aside nav');
            const mainContent = document.querySelector('main');

            if (sidebar) {
                const savedSidebarScroll = sessionStorage.getItem('sidebarScrollPos');
                if (savedSidebarScroll) {
                    sidebar.scrollTop = parseInt(savedSidebarScroll, 10);
                }
            }

            if (mainContent) {
                const savedMainScroll = sessionStorage.getItem('mainScrollPos');
                if (savedMainScroll) {
                    mainContent.scrollTop = parseInt(savedMainScroll, 10);
                }
            }

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

            const copyButton = document.querySelector('[data-copy-target="verification-link"]');
            if (copyButton) {
                copyButton.addEventListener('click', async function() {
                    const input = document.getElementById('verification-link');
                    if (!input || !input.value) {
                        return;
                    }

                    try {
                        await navigator.clipboard.writeText(input.value);
                        copyButton.textContent = 'Copied!';
                        setTimeout(() => {
                            copyButton.textContent = 'Copy Verification Link';
                        }, 1200);
                    } catch (error) {
                        copyButton.textContent = 'Copy Failed';
                        setTimeout(() => {
                            copyButton.textContent = 'Copy Verification Link';
                        }, 1200);
                    }
                });
            }
        });
    </script>
    @stack('scripts')
</body>
</html>
