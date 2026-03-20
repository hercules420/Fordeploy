@php
    $marketplaceSearch = $searchTerm ?? request('q', '');
    $isCustomer = auth()->check() && in_array((string) auth()->user()->role, ['consumer', 'client'], true);
    $cartCount = collect(session('cart', []))->sum(fn($item) => (int) ($item['quantity'] ?? 0));
@endphp

@if($isCustomer)
<nav class="sticky top-0 z-30 mb-5 rounded-2xl border border-gray-700 bg-gray-800/95 p-3 backdrop-blur">
    <div class="flex items-center justify-between gap-3">
        <div class="flex items-center gap-2 text-sm text-gray-300">
            <span class="rounded-lg bg-orange-600 px-2 py-1 text-xs font-bold text-white">Marketplace</span>
            <span class="hidden sm:inline">Shop and manage your orders</span>
        </div>

        <button
            type="button"
            class="marketplace-nav-toggle rounded-lg border border-gray-600 px-3 py-1.5 text-sm text-gray-200 hover:bg-gray-700 md:hidden"
            aria-expanded="false"
        >
            Menu
        </button>
    </div>

    <div class="marketplace-nav-content mt-3 hidden md:block">
        <form method="GET" action="{{ route('products.index') }}" class="flex w-full items-center gap-2 md:max-w-xl">
            @if(request()->filled('farm_owner_id'))
                <input type="hidden" name="farm_owner_id" value="{{ request('farm_owner_id') }}">
            @endif
            <input
                type="text"
                name="q"
                value="{{ $marketplaceSearch }}"
                placeholder="Search products, categories, or keywords"
                class="w-full rounded-lg border border-gray-600 bg-gray-900 px-3 py-2 text-sm text-white placeholder-gray-400 focus:border-orange-500 focus:outline-none"
            >
            <button type="submit" class="rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700">
                Search
            </button>
        </form>

        <div class="mt-3 flex flex-wrap items-center gap-2 text-sm">
            <a href="{{ route('products.index') }}" class="rounded-lg border border-gray-600 px-3 py-1.5 text-gray-200 hover:bg-gray-700">Shop</a>
            <a href="{{ route('marketplace.profile.edit') }}" class="rounded-lg border border-gray-600 px-3 py-1.5 text-gray-200 hover:bg-gray-700">Profile</a>
            <a href="{{ route('orders.index') }}" class="rounded-lg border border-gray-600 px-3 py-1.5 text-gray-200 hover:bg-gray-700">My Orders</a>
            <a href="{{ route('checkout') }}" class="rounded-lg border border-gray-600 px-3 py-1.5 text-gray-200 hover:bg-gray-700">
                Cart
                <span id="marketplace-cart-count" class="ml-1 inline-flex min-w-5 justify-center rounded-full bg-orange-600 px-1.5 text-xs font-semibold text-white">{{ $cartCount }}</span>
            </a>
            <a href="{{ route('marketplace.notifications') }}" class="rounded-lg border border-gray-600 px-3 py-1.5 text-gray-200 hover:bg-gray-700">Notifications</a>
            <a href="{{ route('marketplace.ratings') }}" class="rounded-lg border border-gray-600 px-3 py-1.5 text-gray-200 hover:bg-gray-700">Order Ratings</a>
        </div>
    </div>
</nav>

<script>
    (function () {
        const navToggles = document.querySelectorAll('.marketplace-nav-toggle');
        navToggles.forEach((btn) => {
            btn.addEventListener('click', () => {
                const nav = btn.closest('nav');
                if (!nav) return;

                const content = nav.querySelector('.marketplace-nav-content');
                if (!content) return;

                content.classList.toggle('hidden');
                btn.setAttribute('aria-expanded', content.classList.contains('hidden') ? 'false' : 'true');
            });
        });

        // Pages can dispatch this event after cart updates.
        window.addEventListener('marketplace-cart-updated', (event) => {
            const nextCount = Number(event?.detail?.count ?? NaN);
            if (Number.isNaN(nextCount)) return;

            document.querySelectorAll('#marketplace-cart-count').forEach((badge) => {
                badge.textContent = String(nextCount);
            });

            document.querySelectorAll('[data-marketplace-checkout-count]').forEach((counter) => {
                counter.textContent = String(nextCount);
            });
        });
    })();
</script>
@endif
