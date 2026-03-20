<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $product->name }} - Product</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-900 text-gray-100 min-h-screen">
    <div class="max-w-5xl mx-auto px-4 py-6">
        @include('marketplace.partials.navbar')

        <a href="{{ route('products.index') }}" class="text-orange-300 hover:underline text-sm">&larr; Back to shop</a>

        <div class="mt-3 grid md:grid-cols-2 gap-6 rounded-2xl border border-gray-700 bg-gray-800 p-5 shadow-sm">
            <div>
                @if($product->image_url)
                    <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="w-full h-80 object-cover rounded-lg">
                @else
                    <div class="w-full h-80 bg-gray-700 rounded-lg flex items-center justify-center text-gray-400">No Image</div>
                @endif
            </div>

            <div>
                <a href="{{ route('products.index', ['farm_owner_id' => $product->farm_owner_id]) }}" class="text-sm text-orange-300 hover:underline">{{ $product->farmOwner?->farm_name ?? 'Unknown Farm' }}</a>
                @php
                    $shopRating = (float) ($product->farmOwner?->average_rating ?? 0);
                    $isSupplyShop = in_array($product->category, ['feeds', 'equipment', 'other'], true);
                    $customChoices = collect($product->normalized_order_quantity_options ?? [])
                        ->filter(fn($value) => (int) $value <= (int) $product->quantity_available)
                        ->values()
                        ->all();
                    $hasCustomChoices = count($customChoices) > 0;
                    $isBulkProduct = (bool) $product->is_bulk_order_enabled;
                    $orderStep = max(1, (int) ($product->order_quantity_step ?? 1));
                    $bulkChoices = [];
                    if ($isBulkProduct) {
                        for ($i = 1; $i <= 10; $i++) {
                            $choice = $orderStep * $i;
                            if ($choice > (int) $product->quantity_available) {
                                break;
                            }
                            $bulkChoices[] = $choice;
                        }
                    }
                    $choiceOptions = $hasCustomChoices ? $customChoices : ($isBulkProduct ? $bulkChoices : []);
                @endphp
                <div class="mt-2 flex items-center gap-2 text-sm">
                    <span class="text-amber-500">
                        @for($i = 1; $i <= 5; $i++)
                            {{ $shopRating >= $i ? '★' : '☆' }}
                        @endfor
                    </span>
                    <span class="text-gray-300">{{ number_format($shopRating, 1) }}/5</span>
                    <span class="px-2 py-1 rounded-full text-xs font-bold {{ $isSupplyShop ? 'bg-teal-100 text-teal-800' : 'bg-rose-100 text-rose-800' }}">
                        {{ $isSupplyShop ? 'Supply Shop' : 'Poultry Shop' }}
                    </span>
                </div>
                <h1 class="text-3xl font-extrabold mt-1">{{ $product->name }}</h1>
                <p class="mt-2 text-gray-300">{{ $product->description ?: 'Fresh stock available.' }}</p>

                <div class="mt-4 text-lg font-bold text-orange-300">PHP {{ number_format((float)$product->price, 2) }}</div>
                <p id="estimatedTotal" class="mt-1 text-sm text-amber-200">Estimated total: PHP {{ number_format((float)$product->price * ((int) ($choiceOptions[0] ?? max(1, (int) $product->minimum_order))), 2) }}</p>
                <p class="mt-1 text-gray-400 text-sm">Stock: {{ $product->quantity_available }} {{ $product->unit }}</p>

                @if($hasCustomChoices || $isBulkProduct)
                    <div class="mt-5">
                        <p class="text-sm text-slate-300 mb-1">Quantity</p>
                        <p class="text-xs text-gray-400">
                            {{ $hasCustomChoices
                                ? 'Tap Add to Cart to pick one of the farm owner quantity choices.'
                                : 'Tap Add to Cart to pick a bulk quantity option.' }}
                        </p>
                        <p class="mt-2 text-xs text-amber-200">
                            {{ $hasCustomChoices
                                ? 'Available choices: ' . implode(', ', array_map(fn($qty) => $qty . ' ' . $product->unit, $choiceOptions))
                                : 'Bulk options start at ' . ($choiceOptions[0] ?? $orderStep) . ' ' . $product->unit . ' (step ' . $orderStep . ').' }}
                        </p>
                    </div>
                    <input id="qty" type="hidden" value="{{ (int) ($choiceOptions[0] ?? $orderStep) }}">
                @else
                    <div class="mt-5 flex items-center gap-2">
                        <label for="qty" class="text-sm text-slate-300">Quantity</label>
                        <input id="qty" type="number" min="{{ max(1, (int) $product->minimum_order) }}" max="{{ $product->quantity_available }}" value="{{ max(1, (int) $product->minimum_order) }}" class="w-24 px-2 py-1 rounded bg-white text-black border border-slate-300">
                    </div>
                    @if((int) $product->minimum_order > 1)
                        <p class="mt-2 text-xs text-gray-400">Minimum order: {{ (int) $product->minimum_order }} {{ $product->unit }}</p>
                    @endif
                @endif

                <button id="addBtn" type="button" class="mt-4 w-full px-4 py-3 rounded-lg bg-orange-600 hover:bg-orange-500 font-semibold text-white">Add to Cart</button>
            </div>
        </div>
    </div>

    <div id="qtyModal" class="fixed inset-0 z-50 hidden">
        <div id="qtyModalBackdrop" class="absolute inset-0 bg-black/60"></div>
        <div class="absolute inset-x-0 bottom-0 rounded-t-2xl border border-gray-700 bg-gray-800 p-4 shadow-xl max-h-[75vh] overflow-y-auto">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-bold text-white">Choose quantity</h2>
                <button id="qtyModalClose" type="button" class="px-2 py-1 rounded text-gray-300 hover:bg-gray-700">✕</button>
            </div>
            <p class="mt-1 text-sm text-gray-300">{{ $product->name }}</p>
            <p class="mt-1 text-xs text-gray-400">Price: PHP {{ number_format((float) $product->price, 2) }} per {{ $product->unit }}</p>

            <div id="qtyChoiceList" class="mt-3 grid grid-cols-2 gap-2"></div>

            <div id="qtyPackCountWrap" class="mt-3 rounded-lg border border-gray-700 bg-gray-900/60 p-3 hidden">
                <p class="text-xs text-gray-400 mb-2">How many packs of selected choice?</p>
                <div class="flex items-center gap-2">
                    <button id="qtyPackMinus" type="button" class="px-3 py-1 rounded bg-slate-700 hover:bg-slate-600 text-sm">-</button>
                    <input id="qtyPackCountInput" type="number" min="1" value="1" class="w-20 rounded bg-white text-black border border-slate-300 px-2 py-1 text-sm">
                    <button id="qtyPackPlus" type="button" class="px-3 py-1 rounded bg-slate-700 hover:bg-slate-600 text-sm">+</button>
                </div>
            </div>

            <div class="mt-4 rounded-lg border border-orange-500/40 bg-orange-500/10 px-3 py-2">
                <p id="qtyModalSelection" class="text-sm text-orange-100">Selected: -</p>
                <p id="qtyModalTotal" class="text-sm font-semibold text-orange-200">Total: PHP 0.00</p>
            </div>

            <button id="qtyModalConfirm" type="button" class="mt-4 w-full px-4 py-3 rounded-lg bg-orange-600 hover:bg-orange-500 font-semibold text-white">
                Add Selected Quantity to Cart
            </button>
        </div>
    </div>

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const addBtn = document.getElementById('addBtn');
        const qtyInput = document.getElementById('qty');
        const estimatedTotal = document.getElementById('estimatedTotal');
        const hasCustomChoices = @json($hasCustomChoices);
        const isBulkProduct = @json($isBulkProduct);
        const choiceOptions = @json($choiceOptions);
        const choiceMode = hasCustomChoices || isBulkProduct;
        const unitPrice = Number(@json((float) $product->price));
        const unitLabel = @json((string) $product->unit);

        const qtyModal = document.getElementById('qtyModal');
        const qtyModalBackdrop = document.getElementById('qtyModalBackdrop');
        const qtyModalClose = document.getElementById('qtyModalClose');
        const qtyModalConfirm = document.getElementById('qtyModalConfirm');
        const qtyChoiceList = document.getElementById('qtyChoiceList');
        const qtyModalSelection = document.getElementById('qtyModalSelection');
        const qtyModalTotal = document.getElementById('qtyModalTotal');
        const qtyPackCountWrap = document.getElementById('qtyPackCountWrap');
        const qtyPackMinus = document.getElementById('qtyPackMinus');
        const qtyPackPlus = document.getElementById('qtyPackPlus');
        const qtyPackCountInput = document.getElementById('qtyPackCountInput');

        let selectedChoiceQty = Number(qtyInput?.value || choiceOptions[0] || 1);
        let selectedPackCount = 1;

        function formatPhp(amount) {
            const value = Number(amount || 0);
            return `PHP ${value.toFixed(2)}`;
        }

        function updateEstimatedTotal(quantity) {
            if (!estimatedTotal) {
                return;
            }

            const qty = Number(quantity || 0);
            if (!Number.isFinite(qty) || qty <= 0) {
                estimatedTotal.textContent = 'Estimated total: PHP 0.00';
                return;
            }

            const total = unitPrice * qty;
            estimatedTotal.textContent = `Estimated total: ${formatPhp(total)}`;
        }

        updateEstimatedTotal(qtyInput?.value);

        function renderChoiceButtons() {
            if (!qtyChoiceList) {
                return;
            }

            qtyChoiceList.innerHTML = '';

            for (const qty of choiceOptions) {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'choice-btn rounded-lg border border-gray-600 bg-gray-700 px-3 py-2 text-left hover:border-orange-400 hover:bg-orange-500/10';
                button.dataset.qty = String(qty);
                button.innerHTML = `
                    <span class="block text-sm font-semibold text-white">${qty} ${unitLabel}</span>
                    <span class="block text-xs text-amber-200">${formatPhp(unitPrice * qty)}</span>
                `;

                if (Number(qty) === Number(selectedChoiceQty)) {
                    button.classList.add('border-orange-500', 'bg-orange-500/20');
                }

                button.addEventListener('click', () => {
                    selectedChoiceQty = Number(qty);
                    for (const each of qtyChoiceList.querySelectorAll('.choice-btn')) {
                        each.classList.remove('border-orange-500', 'bg-orange-500/20');
                    }
                    button.classList.add('border-orange-500', 'bg-orange-500/20');
                    updateChoiceSummary();
                });

                qtyChoiceList.appendChild(button);
            }
        }

        function updateChoiceSummary() {
            if (!qtyModalSelection || !qtyModalTotal) {
                return;
            }

            const packCount = hasCustomChoices ? Math.max(1, Number(qtyPackCountInput?.value || selectedPackCount || 1)) : 1;
            const effectiveQty = hasCustomChoices ? (selectedChoiceQty * packCount) : selectedChoiceQty;

            if (hasCustomChoices) {
                qtyModalSelection.textContent = `Selected: ${selectedChoiceQty} ${unitLabel} x ${packCount} pack(s) = ${effectiveQty} ${unitLabel}`;
            } else {
                qtyModalSelection.textContent = `Selected: ${selectedChoiceQty} ${unitLabel}`;
            }

            qtyModalTotal.textContent = `Total: ${formatPhp(unitPrice * effectiveQty)}`;
            updateEstimatedTotal(effectiveQty);
        }

        function openQuantityModal() {
            if (!qtyModal) {
                return;
            }

            if (!Array.isArray(choiceOptions) || choiceOptions.length === 0) {
                alert(hasCustomChoices
                    ? 'No quantity choice is available for current stock.'
                    : 'No bulk quantity option is available for current stock.');
                return;
            }

            selectedChoiceQty = Number(qtyInput?.value || choiceOptions[0]);
            selectedPackCount = 1;

            if (qtyPackCountWrap) {
                qtyPackCountWrap.classList.toggle('hidden', !hasCustomChoices);
            }

            if (qtyPackCountInput) {
                qtyPackCountInput.value = '1';
            }

            renderChoiceButtons();
            updateChoiceSummary();
            qtyModal.classList.remove('hidden');
        }

        function closeQuantityModal() {
            if (!qtyModal) {
                return;
            }
            qtyModal.classList.add('hidden');
        }

        if (choiceMode && (!Array.isArray(choiceOptions) || choiceOptions.length === 0)) {
            addBtn.disabled = true;
            updateEstimatedTotal(0);
        }

        if (qtyModalBackdrop) {
            qtyModalBackdrop.addEventListener('click', closeQuantityModal);
        }
        if (qtyModalClose) {
            qtyModalClose.addEventListener('click', closeQuantityModal);
        }
        if (qtyModalConfirm) {
            qtyModalConfirm.addEventListener('click', () => {
                const packCount = hasCustomChoices ? Math.max(1, Number(qtyPackCountInput?.value || '1')) : 1;
                const effectiveQty = hasCustomChoices ? (selectedChoiceQty * packCount) : selectedChoiceQty;
                if (qtyInput) {
                    qtyInput.value = String(effectiveQty);
                }
                closeQuantityModal();
                performAddToCart(effectiveQty);
            });
        }

        if (qtyPackMinus) {
            qtyPackMinus.addEventListener('click', () => {
                const next = Math.max(1, Number(qtyPackCountInput?.value || '1') - 1);
                if (qtyPackCountInput) {
                    qtyPackCountInput.value = String(next);
                }
                selectedPackCount = next;
                updateChoiceSummary();
            });
        }

        if (qtyPackPlus) {
            qtyPackPlus.addEventListener('click', () => {
                const next = Math.max(1, Number(qtyPackCountInput?.value || '1') + 1);
                if (qtyPackCountInput) {
                    qtyPackCountInput.value = String(next);
                }
                selectedPackCount = next;
                updateChoiceSummary();
            });
        }

        if (qtyPackCountInput) {
            qtyPackCountInput.addEventListener('input', () => {
                const next = Math.max(1, Number(qtyPackCountInput.value || '1'));
                qtyPackCountInput.value = String(next);
                selectedPackCount = next;
                updateChoiceSummary();
            });
        }

        if (qtyInput && !choiceMode) {
            qtyInput.addEventListener('input', () => updateEstimatedTotal(qtyInput.value));
            qtyInput.addEventListener('change', () => updateEstimatedTotal(qtyInput.value));
        }

        async function performAddToCart(quantity) {
            const qty = Number(quantity || qtyInput?.value || '1');
            addBtn.disabled = true;

            try {
                const response = await fetch('{{ route('cart.add') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        product_id: {{ $product->id }},
                        quantity: qty
                    })
                });

                const data = await response.json();
                if (!response.ok) {
                    alert(data.error || 'Failed to add to cart.');
                    return;
                }

                window.dispatchEvent(new CustomEvent('marketplace-cart-updated', {
                    detail: { count: Number(data.cart_count ?? 0) }
                }));

                window.location.href = '{{ route('checkout') }}';
            } catch (error) {
                alert('Could not add item to cart.');
            } finally {
                addBtn.disabled = false;
            }
        }

        addBtn.addEventListener('click', () => {
            if (choiceMode) {
                openQuantityModal();
                return;
            }

            performAddToCart(Number(qtyInput?.value || '1'));
        });
    </script>
</body>
</html>
