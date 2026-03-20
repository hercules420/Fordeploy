<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Poultry System</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-900 text-gray-100 min-h-screen">
    <div class="max-w-6xl mx-auto px-4 py-6">
        @include('marketplace.partials.navbar')

        <div class="flex justify-between items-center mb-4">
            <h1 class="text-2xl font-extrabold">Checkout</h1>
            <a href="{{ route('products.index') }}" class="text-orange-300 hover:underline text-sm">Continue shopping</a>
        </div>

        @if (session('error'))
            <div class="mb-4 rounded-lg border border-red-500/40 bg-red-900/30 p-3 text-red-200">{{ session('error') }}</div>
        @endif

        @if (session('success'))
            <div class="mb-4 rounded-lg border border-green-500/40 bg-green-900/30 p-3 text-green-200">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="mb-4 rounded-lg border border-red-500/40 bg-red-900/30 p-3 text-red-200">
                <ul class="list-disc list-inside text-sm space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid lg:grid-cols-3 gap-5">
            <div class="lg:col-span-2 rounded-xl border border-gray-700 bg-gray-800 p-4 shadow-sm">
                <h2 class="font-bold mb-3">Items in Cart</h2>
                <div class="space-y-3">
                    @foreach($cart as $item)
                        @php
                            $itemChoices = collect($item['order_quantity_options'] ?? [])->map(fn($value) => (int) $value)->filter(fn($value) => $value > 0)->values()->all();
                            $hasCustomChoices = count($itemChoices) > 0;
                            $itemStep = !empty($item['is_bulk_order_enabled']) ? max(1, (int) ($item['order_quantity_step'] ?? 1)) : 1;
                            $itemMinimum = max(1, (int) ($item['minimum_order'] ?? 1));
                        @endphp
                        <div class="rounded-lg border border-gray-700 p-3 flex justify-between gap-3">
                            <div>
                                <p class="font-semibold">{{ $item['name'] }}</p>
                                @if($hasCustomChoices)
                                    <p class="text-xs text-amber-300 mt-1">Choices only: {{ implode(', ', $itemChoices) }} {{ $item['unit'] ?? 'unit' }}</p>
                                @elseif(!empty($item['is_bulk_order_enabled']))
                                    <p class="text-xs text-amber-300 mt-1">Bulk mode: multiples of {{ $itemStep }} {{ $item['unit'] ?? 'unit' }}</p>
                                @elseif($itemMinimum > 1)
                                    <p class="text-xs text-amber-300 mt-1">Minimum order: {{ $itemMinimum }} {{ $item['unit'] ?? 'unit' }}</p>
                                @endif
                                <div class="mt-2 flex items-center gap-2">
                                    <p class="text-xs text-gray-300">Qty: <span class="font-semibold">{{ (int) $item['quantity'] }}</span></p>
                                    <button
                                        type="button"
                                        class="open-qty-modal text-xs px-2 py-1 rounded bg-slate-700 hover:bg-slate-600"
                                        data-product-id="{{ $item['product_id'] }}"
                                        data-product-name="{{ $item['name'] }}"
                                        data-unit="{{ $item['unit'] ?? 'unit' }}"
                                        data-price="{{ (float) $item['price'] }}"
                                        data-current-qty="{{ (int) $item['quantity'] }}"
                                        data-choice-list="{{ implode(',', $itemChoices) }}"
                                        data-has-custom-choice="{{ $hasCustomChoices ? '1' : '0' }}"
                                        data-step="{{ $itemStep }}"
                                        data-minimum="{{ $itemMinimum }}"
                                    >
                                        Change quantity
                                    </button>

                                    <form method="POST" action="{{ route('cart.update') }}" class="cart-qty-form hidden">
                                        @csrf
                                        <input type="hidden" name="product_id" value="{{ $item['product_id'] }}">
                                        <input type="hidden" name="quantity" value="{{ (int) $item['quantity'] }}">
                                    </form>

                                    <form method="POST" action="{{ route('cart.remove') }}" onsubmit="return confirm('Remove this item from cart?')">
                                        @csrf
                                        <input type="hidden" name="product_id" value="{{ $item['product_id'] }}">
                                        <button type="submit" class="text-xs px-2 py-1 rounded bg-red-700/80 hover:bg-red-600">Remove</button>
                                    </form>
                                </div>
                            </div>
                            <div class="font-semibold text-orange-300">PHP {{ number_format((float)$item['price'] * (int)$item['quantity'], 2) }}</div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="rounded-xl border border-gray-700 bg-gray-800 p-4 shadow-sm">
                <h2 class="font-bold mb-3">Delivery Details</h2>
                <form method="POST" action="{{ route('orders.place') }}" class="space-y-3">
                    @csrf

                    <div>
                        <label class="block text-xs mb-1 text-slate-600">Delivery Type</label>
                        <select name="delivery_type" id="delivery_type" class="w-full rounded bg-white text-black border border-slate-300 px-3 py-2" required>
                            <option value="delivery" {{ old('delivery_type', 'delivery') === 'delivery' ? 'selected' : '' }}>Delivery</option>
                            <option value="pickup" {{ old('delivery_type') === 'pickup' ? 'selected' : '' }}>Pickup</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs mb-1 text-slate-600">Payment Method</label>
                        <select name="payment_method" class="w-full rounded bg-white text-black border border-slate-300 px-3 py-2" required>
                            <option value="cod" {{ old('payment_method', 'cod') === 'cod' ? 'selected' : '' }}>Cash on Delivery (COD)</option>
                            <option value="gcash" {{ old('payment_method') === 'gcash' ? 'selected' : '' }}>GCash (PayMongo)</option>
                            <option value="paymaya" {{ old('payment_method') === 'paymaya' ? 'selected' : '' }}>PayMaya (PayMongo)</option>
                        </select>
                        <p class="text-xs text-gray-400 mt-1">For GCash or PayMaya, you will be redirected to PayMongo checkout after order creation.</p>
                    </div>

                    <div id="delivery_fields" class="space-y-3">
                        <div>
                            <label class="block text-xs mb-1 text-slate-600">Address</label>
                            <input type="text" name="delivery_address" value="{{ old('delivery_address') }}" class="w-full rounded bg-white text-black border border-slate-300 px-3 py-2">
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-xs mb-1 text-slate-600">City</label>
                                <input type="text" name="delivery_city" value="{{ old('delivery_city') }}" class="w-full rounded bg-white text-black border border-slate-300 px-3 py-2">
                            </div>
                            <div>
                                <label class="block text-xs mb-1 text-slate-600">Province</label>
                                <input type="text" name="delivery_province" value="{{ old('delivery_province') }}" class="w-full rounded bg-white text-black border border-slate-300 px-3 py-2">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs mb-1 text-slate-600">Postal Code</label>
                            <input type="text" name="delivery_postal_code" value="{{ old('delivery_postal_code') }}" class="w-full rounded bg-white text-black border border-slate-300 px-3 py-2">
                        </div>
                    </div>

                    <div class="rounded-lg border border-gray-700 bg-gray-900 p-3">
                        <div class="flex justify-between text-sm"><span class="text-gray-400">Subtotal</span><span>PHP {{ number_format((float)$total_amount, 2) }}</span></div>
                        <div class="flex justify-between text-sm"><span class="text-gray-400">Tax and shipping</span><span>Calculated on place order</span></div>
                    </div>

                    <div class="sticky bottom-3 bg-gray-800/95 backdrop-blur rounded-lg pt-2">
                        <button type="submit" class="w-full px-4 py-3 rounded-lg bg-orange-600 hover:bg-orange-500 text-white font-semibold shadow-sm">Place Order</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="checkoutQtyModal" class="fixed inset-0 z-50 hidden">
        <div id="checkoutQtyBackdrop" class="absolute inset-0 bg-black/60"></div>
        <div class="absolute inset-x-0 bottom-0 rounded-t-2xl border border-gray-700 bg-gray-800 p-4 shadow-xl max-h-[75vh] overflow-y-auto">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-bold text-white">Choose quantity</h2>
                <button id="checkoutQtyClose" type="button" class="px-2 py-1 rounded text-gray-300 hover:bg-gray-700">x</button>
            </div>
            <p id="checkoutQtyName" class="mt-1 text-sm text-gray-300"></p>
            <p id="checkoutQtyPrice" class="mt-1 text-xs text-gray-400"></p>

            <div id="checkoutQtyChoiceList" class="mt-3 grid grid-cols-2 gap-2"></div>

            <div id="checkoutPackCountWrap" class="mt-3 rounded-lg border border-gray-700 bg-gray-900/60 p-3 hidden">
                <p class="text-xs text-gray-400 mb-2">How many packs of selected choice?</p>
                <div class="flex items-center gap-2">
                    <button id="checkoutPackMinus" type="button" class="px-3 py-1 rounded bg-slate-700 hover:bg-slate-600 text-sm">-</button>
                    <input id="checkoutPackCountInput" type="number" min="1" value="1" class="w-20 rounded bg-white text-black border border-slate-300 px-2 py-1 text-sm">
                    <button id="checkoutPackPlus" type="button" class="px-3 py-1 rounded bg-slate-700 hover:bg-slate-600 text-sm">+</button>
                </div>
            </div>

            <div class="mt-4 rounded-lg border border-orange-500/40 bg-orange-500/10 px-3 py-2">
                <p id="checkoutQtySelected" class="text-sm text-orange-100">Selected: -</p>
                <p id="checkoutQtyTotal" class="text-sm font-semibold text-orange-200">Total: PHP 0.00</p>
            </div>

            <button id="checkoutQtyConfirm" type="button" class="mt-4 w-full px-4 py-3 rounded-lg bg-orange-600 hover:bg-orange-500 font-semibold text-white">
                Update quantity
            </button>
        </div>
    </div>

    <script>
        const deliveryType = document.getElementById('delivery_type');
        const deliveryFields = document.getElementById('delivery_fields');

        function toggleDeliveryFields() {
            deliveryFields.style.display = deliveryType.value === 'delivery' ? 'block' : 'none';
        }

        deliveryType.addEventListener('change', toggleDeliveryFields);
        toggleDeliveryFields();

        const checkoutQtyModal = document.getElementById('checkoutQtyModal');
        const checkoutQtyBackdrop = document.getElementById('checkoutQtyBackdrop');
        const checkoutQtyClose = document.getElementById('checkoutQtyClose');
        const checkoutQtyConfirm = document.getElementById('checkoutQtyConfirm');
        const checkoutQtyName = document.getElementById('checkoutQtyName');
        const checkoutQtyPrice = document.getElementById('checkoutQtyPrice');
        const checkoutQtyChoiceList = document.getElementById('checkoutQtyChoiceList');
        const checkoutQtySelected = document.getElementById('checkoutQtySelected');
        const checkoutQtyTotal = document.getElementById('checkoutQtyTotal');
        const checkoutPackCountWrap = document.getElementById('checkoutPackCountWrap');
        const checkoutPackMinus = document.getElementById('checkoutPackMinus');
        const checkoutPackPlus = document.getElementById('checkoutPackPlus');
        const checkoutPackCountInput = document.getElementById('checkoutPackCountInput');

        let activeQtyButton = null;
        let selectedCheckoutQty = 1;
        let selectedPackCount = 1;

        function formatPhp(amount) {
            const value = Number(amount || 0);
            return `PHP ${value.toFixed(2)}`;
        }

        function buildFallbackChoices(currentQty, minQty, stepQty) {
            const current = Math.max(minQty, currentQty);
            const step = Math.max(1, stepQty);
            const base = Math.max(minQty, current);
            const values = [];
            for (let i = 0; i < 8; i++) {
                values.push(base + (i * step));
            }
            return values;
        }

        function closeCheckoutQtyModal() {
            if (!checkoutQtyModal) return;
            checkoutQtyModal.classList.add('hidden');
        }

        function updateCheckoutQtySummary(button) {
            const unit = button?.dataset.unit || 'unit';
            const price = Number(button?.dataset.price || '0');
            const hasCustomChoice = String(button?.dataset.hasCustomChoice || '0') === '1';
            const packCount = hasCustomChoice ? Math.max(1, Number(checkoutPackCountInput?.value || selectedPackCount || 1)) : 1;
            const effectiveQty = hasCustomChoice ? (selectedCheckoutQty * packCount) : selectedCheckoutQty;

            if (hasCustomChoice) {
                checkoutQtySelected.textContent = `Selected: ${selectedCheckoutQty} ${unit} x ${packCount} pack(s) = ${effectiveQty} ${unit}`;
            } else {
                checkoutQtySelected.textContent = `Selected: ${selectedCheckoutQty} ${unit}`;
            }

            checkoutQtyTotal.textContent = `Total: ${formatPhp(price * effectiveQty)}`;
        }

        function inferPackCount(currentQty, choices) {
            for (const choice of choices) {
                if (choice > 0 && currentQty % choice === 0) {
                    return { baseChoice: choice, packCount: Math.max(1, currentQty / choice) };
                }
            }

            return { baseChoice: choices[0], packCount: 1 };
        }

        function renderCheckoutChoices(button) {
            if (!checkoutQtyChoiceList) return;

            const currentQty = Number(button.dataset.currentQty || '1');
            const minimum = Number(button.dataset.minimum || '1');
            const step = Number(button.dataset.step || '1');
            const explicitChoices = String(button.dataset.choiceList || '')
                .split(',')
                .map((value) => Number(value))
                .filter((value) => Number.isFinite(value) && value > 0);

            const choices = explicitChoices.length > 0
                ? [...new Set(explicitChoices)].sort((a, b) => a - b)
                : buildFallbackChoices(currentQty, minimum, step);
            const hasCustomChoice = String(button.dataset.hasCustomChoice || '0') === '1';

            if (hasCustomChoice) {
                const inferred = inferPackCount(currentQty, choices);
                selectedCheckoutQty = inferred.baseChoice;
                selectedPackCount = inferred.packCount;
                if (checkoutPackCountInput) {
                    checkoutPackCountInput.value = String(selectedPackCount);
                }
            } else {
                selectedCheckoutQty = choices.includes(currentQty) ? currentQty : choices[0];
                selectedPackCount = 1;
                if (checkoutPackCountInput) {
                    checkoutPackCountInput.value = '1';
                }
            }

            if (checkoutPackCountWrap) {
                checkoutPackCountWrap.classList.toggle('hidden', !hasCustomChoice);
            }

            checkoutQtyChoiceList.innerHTML = '';

            for (const qty of choices) {
                const buttonElement = document.createElement('button');
                buttonElement.type = 'button';
                buttonElement.className = 'checkout-choice-btn rounded-lg border border-gray-600 bg-gray-700 px-3 py-2 text-left hover:border-orange-400 hover:bg-orange-500/10';
                buttonElement.dataset.qty = String(qty);

                const price = Number(button.dataset.price || '0');
                const unit = button.dataset.unit || 'unit';
                buttonElement.innerHTML = `
                    <span class="block text-sm font-semibold text-white">${qty} ${unit}</span>
                    <span class="block text-xs text-amber-200">${formatPhp(price * qty)}</span>
                `;

                if (qty === selectedCheckoutQty) {
                    buttonElement.classList.add('border-orange-500', 'bg-orange-500/20');
                }

                buttonElement.addEventListener('click', () => {
                    selectedCheckoutQty = qty;
                    for (const each of checkoutQtyChoiceList.querySelectorAll('.checkout-choice-btn')) {
                        each.classList.remove('border-orange-500', 'bg-orange-500/20');
                    }
                    buttonElement.classList.add('border-orange-500', 'bg-orange-500/20');
                    updateCheckoutQtySummary(button);
                });

                checkoutQtyChoiceList.appendChild(buttonElement);
            }

            updateCheckoutQtySummary(button);
        }

        document.querySelectorAll('.open-qty-modal').forEach((button) => {
            button.addEventListener('click', () => {
                activeQtyButton = button;
                checkoutQtyName.textContent = button.dataset.productName || 'Product';
                checkoutQtyPrice.textContent = `Price: ${formatPhp(button.dataset.price)} per ${button.dataset.unit || 'unit'}`;
                renderCheckoutChoices(button);
                checkoutQtyModal.classList.remove('hidden');
            });
        });

        if (checkoutQtyBackdrop) {
            checkoutQtyBackdrop.addEventListener('click', closeCheckoutQtyModal);
        }

        if (checkoutQtyClose) {
            checkoutQtyClose.addEventListener('click', closeCheckoutQtyModal);
        }

        if (checkoutQtyConfirm) {
            checkoutQtyConfirm.addEventListener('click', () => {
                if (!activeQtyButton) {
                    return;
                }

                const hasCustomChoice = String(activeQtyButton.dataset.hasCustomChoice || '0') === '1';
                const packCount = hasCustomChoice ? Math.max(1, Number(checkoutPackCountInput?.value || '1')) : 1;
                const effectiveQty = hasCustomChoice ? (selectedCheckoutQty * packCount) : selectedCheckoutQty;

                const productId = activeQtyButton.dataset.productId;
                const form = document.querySelector(`.cart-qty-form input[name="product_id"][value="${productId}"]`)?.closest('form');
                const quantityInput = form?.querySelector('input[name="quantity"]');

                if (!form || !quantityInput) {
                    closeCheckoutQtyModal();
                    return;
                }

                quantityInput.value = String(effectiveQty);
                closeCheckoutQtyModal();
                form.submit();
            });
        }

        if (checkoutPackMinus) {
            checkoutPackMinus.addEventListener('click', () => {
                const next = Math.max(1, Number(checkoutPackCountInput?.value || '1') - 1);
                if (checkoutPackCountInput) {
                    checkoutPackCountInput.value = String(next);
                }
                selectedPackCount = next;
                if (activeQtyButton) {
                    updateCheckoutQtySummary(activeQtyButton);
                }
            });
        }

        if (checkoutPackPlus) {
            checkoutPackPlus.addEventListener('click', () => {
                const next = Math.max(1, Number(checkoutPackCountInput?.value || '1') + 1);
                if (checkoutPackCountInput) {
                    checkoutPackCountInput.value = String(next);
                }
                selectedPackCount = next;
                if (activeQtyButton) {
                    updateCheckoutQtySummary(activeQtyButton);
                }
            });
        }

        if (checkoutPackCountInput) {
            checkoutPackCountInput.addEventListener('input', () => {
                const next = Math.max(1, Number(checkoutPackCountInput.value || '1'));
                checkoutPackCountInput.value = String(next);
                selectedPackCount = next;
                if (activeQtyButton) {
                    updateCheckoutQtySummary(activeQtyButton);
                }
            });
        }
    </script>
</body>
</html>
