<x-guest-layout>
    <div class="min-h-screen flex items-center justify-center bg-[#1a202c] p-6">
        <div class="w-full max-w-lg bg-[#111827] border border-gray-700 rounded-3xl p-8 shadow-2xl text-center">
            <h2 class="text-2xl font-black text-[#4fd1c5] uppercase tracking-widest">Consumer Access Ready</h2>
            <p class="text-gray-300 mt-4">
                Your account is ready. Tap the button below to continue to the mobile app login form.
            </p>

            @if (session('success'))
                <div class="mt-4 rounded-xl border border-green-500/40 bg-green-500/10 p-3 text-green-200 text-sm">
                    {{ session('success') }}
                </div>
            @endif

            <button id="goToApp" class="mt-8 w-full bg-[#4fd1c5] hover:bg-[#38b2ac] text-[#111827] font-black py-4 rounded-2xl uppercase tracking-widest">
                Go to the App
            </button>

            <p id="launchStatus" class="mt-3 text-xs text-gray-400 hidden"></p>

            <p class="mt-5 text-xs text-gray-500">
                If the mobile app is not installed on this device, you will be redirected to the web login page.
            </p>
        </div>
    </div>

    <script>
        const appUrl = 'poultryconsumer://login';
        const fallbackUrl = '{{ route('login') }}';
        const launchStatus = document.getElementById('launchStatus');
        let launchTimer = null;

        function openConsumerApp() {
            launchStatus.classList.remove('hidden');
            launchStatus.textContent = 'Trying to open the mobile app...';

            // Try deep-link first.
            window.location.assign(appUrl);

            // If app is not installed or protocol is unsupported, fallback to web login.
            launchTimer = setTimeout(() => {
                launchStatus.textContent = 'App not detected on this device. Redirecting to web login...';
                window.location.assign(fallbackUrl);
            }, 1500);
        }

        document.getElementById('goToApp').addEventListener('click', openConsumerApp);

        // If deep-link succeeds, browser typically becomes hidden; cancel fallback timer.
        document.addEventListener('visibilitychange', () => {
            if (document.hidden && launchTimer) {
                clearTimeout(launchTimer);
            }
        });
    </script>
</x-guest-layout>
