<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Marketplace - Poultry System</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root {
            --bg: #0b1220;
            --ink: #e5e7eb;
            --muted: #94a3b8;
            --line: #334155;
            --brand: #f97316;
            --brand-2: #ea580c;
            --rose: #fb923c;
            --card: #111827;
        }

        body {
            margin: 0;
            background:
                radial-gradient(circle at 12% 10%, rgba(15, 118, 110, 0.13), transparent 30%),
                radial-gradient(circle at 85% 6%, rgba(190, 18, 60, 0.10), transparent 27%),
                var(--bg);
            color: var(--ink);
            font-family: "Trebuchet MS", "Segoe UI", sans-serif;
        }

        .shell { max-width: 1180px; margin: 0 auto; padding: 18px; }
        .promo {
            border: 1px solid #7c2d12;
            background: linear-gradient(120deg, rgba(124, 45, 18, 0.55), rgba(17, 24, 39, 0.92));
            border-radius: 16px;
            padding: 12px 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 14px;
        }
        .promo p { margin: 0; }
        .promo .t { font-weight: 800; color: #ffedd5; }
        .promo .s { font-size: 12px; color: #fdba74; }

        .btn {
            text-decoration: none;
            border-radius: 12px;
            font-weight: 800;
            padding: 10px 14px;
            display: inline-block;
            transition: transform .16s ease;
        }
        .btn:hover { transform: translateY(-1px); }
        .btn-brand { background: var(--brand); color: #fff; }
        .btn-soft { border: 1px solid var(--line); color: var(--ink); background: #0f172a; }
        .btn-warm { background: var(--rose); color: #0b1220; }

        .head {
            display: flex;
            justify-content: space-between;
            align-items: end;
            gap: 12px;
            margin-bottom: 14px;
        }
        .head h1 { margin: 0; font-size: 32px; letter-spacing: .01em; }
        .head p { margin: 3px 0 0; color: var(--muted); }

        .filters {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 16px;
            padding: 12px;
            margin-bottom: 14px;
        }
        .chips { display: flex; flex-wrap: wrap; gap: 8px; }
        .chip {
            border: 1px solid #475569;
            color: #cbd5e1;
            text-decoration: none;
            font-size: 13px;
            border-radius: 999px;
            padding: 8px 12px;
            background: #0f172a;
            font-weight: 700;
        }
        .chip.active {
            background: linear-gradient(135deg, var(--brand), var(--brand-2));
            color: #fff;
            border-color: transparent;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 14px;
        }

        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 10px 28px rgba(15, 23, 42, 0.06);
        }
        .card img, .img-fallback {
            width: 100%; height: 180px; object-fit: cover;
            display: block;
        }
        .img-fallback {
            background: #1f2937;
            color: #94a3b8;
            display: flex; align-items: center; justify-content: center;
            font-weight: 700;
        }
        .body { padding: 14px; }
        .farm {
            color: var(--brand);
            text-decoration: none;
            font-size: 12px;
            font-weight: 700;
        }
        .name { margin: 6px 0 4px; font-size: 18px; font-weight: 800; }
        .shop-meta { display:flex; align-items:center; justify-content:space-between; gap:8px; margin-top:7px; }
        .stars { color:#f59e0b; letter-spacing:1px; font-size: 13px; }
        .shop-tag {
            padding: 4px 8px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 800;
        }
        .shop-tag.poultry { background:#431407; color:#fdba74; }
        .shop-tag.supply { background:#172554; color:#93c5fd; }
        .desc {
            color: var(--muted); font-size: 13px; margin: 0;
            min-height: 36px;
        }
        .meta { margin-top: 10px; display: flex; justify-content: space-between; align-items: center; }
        .stock { color: #cbd5e1; font-size: 13px; font-weight: 700; }
        .price { color: var(--rose); font-size: 18px; font-weight: 900; }
        .actions { margin-top: 12px; display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }

        .empty {
            border: 1px dashed #475569;
            border-radius: 16px;
            text-align: center;
            color: var(--muted);
            background: #111827;
            padding: 46px 20px;
        }
    </style>
</head>
<body>
    <div class="shell">
        @include('marketplace.partials.navbar')

        <section class="promo">
            <div>
                <p class="t">Want to Easily Shop? Download the App!</p>
                <p class="s">Orders and products stay synced with this web marketplace.</p>
            </div>
            <a href="{{ route('consumer.app.launch') }}" class="btn btn-brand">Open App Options</a>
        </section>

        <section class="head">
            <div>
                <h1>Consumer Marketplace</h1>
                <p>Fresh poultry products from verified farms.</p>
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <a href="{{ route('orders.index') }}" class="btn btn-soft">My Orders</a>
                <a href="{{ route('checkout') }}" class="btn btn-brand">
                    Checkout (
                    <span data-marketplace-checkout-count>
                        {{ collect(session('cart', []))->sum(fn($item) => (int) ($item['quantity'] ?? 0)) }}
                    </span>
                    )
                </a>
            </div>
        </section>

        <section class="filters">
            <div style="font-size:13px;font-weight:800;margin-bottom:8px;color:#325665;">Shop by farm owner</div>
            <div class="chips">
                <a href="{{ route('products.index') }}" class="chip {{ empty($selectedFarmOwnerId) ? 'active' : '' }}">All Farms</a>
                @foreach($farmOwners as $owner)
                    <a href="{{ route('products.index', ['farm_owner_id' => $owner->id]) }}" class="chip {{ (int) $selectedFarmOwnerId === $owner->id ? 'active' : '' }}">{{ $owner->farm_name }}</a>
                @endforeach
            </div>
        </section>

        @if (session('success'))
            <div data-marketplace-flash="success" style="margin-bottom:12px;padding:12px;border-radius:12px;border:1px solid #a7f3d0;background:#ecfdf5;color:#065f46;">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div data-marketplace-flash="error" style="margin-bottom:12px;padding:12px;border-radius:12px;border:1px solid #fecaca;background:#fef2f2;color:#991b1b;">{{ session('error') }}</div>
        @endif

        @if($products->count() === 0)
            <div class="empty">No products available for this farm yet.</div>
        @else
            <section class="grid">
                @foreach($products as $product)
                    <article class="card">
                        @if($product->image_url)
                            <img src="{{ $product->image_url }}" alt="{{ $product->name }}">
                        @else
                            <div class="img-fallback">No Image</div>
                        @endif
                        <div class="body">
                            <a href="{{ route('products.index', ['farm_owner_id' => $product->farm_owner_id]) }}" class="farm">{{ $product->farmOwner?->farm_name ?? 'Unknown Farm' }}</a>
                            @php
                                $shopRating = (float) ($product->farmOwner?->average_rating ?? 0);
                                $isSupplyShop = in_array($product->category, ['feeds', 'equipment', 'other'], true);
                            @endphp
                            <h2 class="name">{{ $product->name }}</h2>
                            <div class="shop-meta">
                                <div class="stars" title="Shop rating {{ number_format($shopRating, 1) }}/5">
                                    @for($i = 1; $i <= 5; $i++)
                                        {{ $shopRating >= $i ? '★' : '☆' }}
                                    @endfor
                                    <span style="font-size:11px;color:#64748b;margin-left:4px;">{{ number_format($shopRating, 1) }}</span>
                                </div>
                                <span class="shop-tag {{ $isSupplyShop ? 'supply' : 'poultry' }}">{{ $isSupplyShop ? 'Supply Shop' : 'Poultry Shop' }}</span>
                            </div>
                            <p class="desc">{{ $product->description ?: 'Fresh stock available.' }}</p>
                            <div class="meta">
                                <span class="stock">Stock: {{ $product->quantity_available }}</span>
                                <span class="price">PHP {{ number_format((float)$product->price, 2) }}</span>
                            </div>
                            <div class="actions">
                                <a href="{{ route('products.show', $product) }}" class="btn btn-soft" style="text-align:center;">View</a>
                                <button
                                    type="button"
                                    class="btn btn-brand add-to-cart"
                                    data-id="{{ $product->id }}"
                                    data-requires-choice="{{ ($product->is_bulk_order_enabled || (int) $product->minimum_order > 1 || !empty($product->normalized_order_quantity_options)) ? '1' : '0' }}"
                                    data-view-url="{{ route('products.show', $product) }}"
                                >
                                    {{ ($product->is_bulk_order_enabled || (int) $product->minimum_order > 1 || !empty($product->normalized_order_quantity_options)) ? 'Choose Qty' : 'Add to Cart' }}
                                </button>
                            </div>
                        </div>
                    </article>
                @endforeach
            </section>
            <div style="margin-top:18px;">{{ $products->withQueryString()->links() }}</div>
        @endif
    </div>

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        document.querySelectorAll('.add-to-cart').forEach((btn) => {
            btn.addEventListener('click', async () => {
                if (btn.dataset.requiresChoice === '1') {
                    window.location.href = btn.dataset.viewUrl;
                    return;
                }

                const productId = btn.dataset.id;
                btn.disabled = true;

                try {
                    const response = await fetch('{{ route('cart.add') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ product_id: productId, quantity: 1 })
                    });

                    const data = await response.json();
                    if (!response.ok) {
                        alert(data.error || 'Failed to add to cart.');
                        return;
                    }

                    window.dispatchEvent(new CustomEvent('marketplace-cart-updated', {
                        detail: { count: Number(data.cart_count ?? 0) }
                    }));

                    const nextCount = Number(data.cart_count ?? 0);
                    if (nextCount > 0) {
                        document.querySelectorAll('[data-marketplace-flash]').forEach((flash) => {
                            flash.remove();
                        });
                    }

                    const originalText = btn.textContent;
                    btn.textContent = 'Added';
                    setTimeout(() => {
                        btn.textContent = originalText;
                    }, 900);
                } catch (error) {
                    alert('Could not add item to cart.');
                } finally {
                    btn.disabled = false;
                }
            });
        });
    </script>
</body>
</html>
