@extends('farmowner.layouts.app')

@section('title', 'Edit Product')
@section('header', 'Edit Product')
@section('subheader', $product->sku)

@section('content')
<div class="max-w-2xl">
    @if(($isAtProductLimit ?? false))
    <div class="mb-4 rounded-lg border border-yellow-500/40 bg-yellow-500/10 px-4 py-3 text-sm text-yellow-200">
        You have reached your product limit. Core product details are locked. You can still update stock, pricing, and status.
    </div>
    @endif

    <form action="{{ route('products.update', $product) }}" method="POST" class="bg-gray-800 border border-gray-700 rounded-lg p-6">
        @csrf
        @method('PUT')
        
        <!-- Basic Info -->
        <h3 class="font-semibold text-lg mb-4 pb-2 border-b border-gray-600 text-white">Product Information</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">SKU *</label>
                <input type="text" name="sku" value="{{ old('sku', $product->sku) }}" required
                    @if(($isAtProductLimit ?? false)) readonly @endif
                    class="w-full px-3 py-2 bg-gray-700 border border-gray-600 text-white placeholder-gray-400 rounded-lg focus:ring-2 focus:ring-green-500 @error('sku') border-red-500 @enderror">
                @error('sku')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Category *</label>
                @if(($isAtProductLimit ?? false))
                {{-- Hidden input ensures the value is always submitted even though the select is visually disabled --}}
                <input type="hidden" name="category" value="{{ $product->category }}">
                @endif
                <select name="{{ ($isAtProductLimit ?? false) ? '_category_readonly' : 'category' }}" required
                    @if(($isAtProductLimit ?? false)) disabled aria-disabled="true" @endif
                    class="w-full px-3 py-2 bg-gray-700 border border-gray-600 text-white rounded-lg focus:ring-2 focus:ring-green-500">
                    <option value="live_stock" {{ old('category', $product->category) === 'live_stock' ? 'selected' : '' }}>Live Stock</option>
                    <option value="breeding" {{ old('category', $product->category) === 'breeding' ? 'selected' : '' }}>Breeding</option>
                    <option value="fighting_cock" {{ old('category', $product->category) === 'fighting_cock' ? 'selected' : '' }}>Fighting Cock</option>
                    <option value="eggs" {{ old('category', $product->category) === 'eggs' ? 'selected' : '' }}>Eggs</option>
                    <option value="feeds" {{ old('category', $product->category) === 'feeds' ? 'selected' : '' }}>Feeds</option>
                    <option value="equipment" {{ old('category', $product->category) === 'equipment' ? 'selected' : '' }}>Equipment</option>
                    <option value="other" {{ old('category', $product->category) === 'other' ? 'selected' : '' }}>Other</option>
                </select>
                <p class="text-xs text-amber-300 mt-1">Tip: Use Order Mode below to set normal or maramihan behavior for this product in both web and app marketplace.</p>
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-300 mb-1">Product Name *</label>
                <input type="text" name="name" value="{{ old('name', $product->name) }}" required
                    @if(($isAtProductLimit ?? false)) readonly @endif
                    class="w-full px-3 py-2 bg-gray-700 border border-gray-600 text-white placeholder-gray-400 rounded-lg focus:ring-2 focus:ring-green-500 @error('name') border-red-500 @enderror">
                @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-300 mb-1">Description</label>
                <textarea name="description" rows="3"
                    @if(($isAtProductLimit ?? false)) readonly @endif
                    class="w-full px-3 py-2 bg-gray-700 border border-gray-600 text-white placeholder-gray-400 rounded-lg focus:ring-2 focus:ring-green-500">{{ old('description', $product->description) }}</textarea>
            </div>
        </div>

        <!-- Pricing -->
        <h3 class="font-semibold text-lg mb-4 pb-2 border-b border-gray-600 text-white">Pricing & Inventory</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Selling Price (₱) *</label>
                <input type="number" name="price" value="{{ old('price', $product->price) }}" step="0.01" min="0" required
                    class="w-full px-3 py-2 bg-gray-700 border border-gray-600 text-white placeholder-gray-400 rounded-lg focus:ring-2 focus:ring-green-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Cost Price (₱)</label>
                <input type="number" name="cost_price" value="{{ old('cost_price', $product->cost_price) }}" step="0.01" min="0"
                    class="w-full px-3 py-2 bg-gray-700 border border-gray-600 text-white placeholder-gray-400 rounded-lg focus:ring-2 focus:ring-green-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Quantity Available *</label>
                <input type="number" name="quantity_available" value="{{ old('quantity_available', $product->quantity_available) }}" min="0" required
                    class="w-full px-3 py-2 bg-gray-700 border border-gray-600 text-white placeholder-gray-400 rounded-lg focus:ring-2 focus:ring-green-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Unit *</label>
                <input type="text" name="unit" value="{{ old('unit', $product->unit) }}" required
                    class="w-full px-3 py-2 bg-gray-700 border border-gray-600 text-white placeholder-gray-400 rounded-lg focus:ring-2 focus:ring-green-500">
            </div>
            <div id="minimum_order_wrap">
                <label class="block text-sm font-medium text-gray-300 mb-1">Minimum Order</label>
                <input type="number" id="minimum_order" name="minimum_order" value="{{ old('minimum_order', $product->minimum_order) }}" min="1"
                    class="w-full px-3 py-2 bg-gray-700 border border-gray-600 text-white placeholder-gray-400 rounded-lg focus:ring-2 focus:ring-green-500">
                <p id="minimum_order_hint" class="text-xs text-gray-400 mt-1">Used when there are no custom quantity choices.</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Order Mode *</label>
                <select name="is_bulk_order_enabled" id="is_bulk_order_enabled"
                    class="w-full px-3 py-2 bg-gray-700 border border-gray-600 text-white rounded-lg focus:ring-2 focus:ring-green-500">
                    <option value="0" {{ old('is_bulk_order_enabled', $product->is_bulk_order_enabled ? '1' : '0') === '0' ? 'selected' : '' }}>Normal (any quantity)</option>
                    <option value="1" {{ old('is_bulk_order_enabled', $product->is_bulk_order_enabled ? '1' : '0') === '1' ? 'selected' : '' }}>Bulk only (maramihan)</option>
                </select>
                <p class="text-xs text-gray-400 mt-1">Choose Bulk if buyers must order in fixed quantity steps.</p>
            </div>
            <div id="order_quantity_step_wrap" class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-300 mb-1">Bulk Quantity Step *</label>
                <input type="number" name="order_quantity_step" id="order_quantity_step" value="{{ old('order_quantity_step', $product->order_quantity_step ?? 1) }}" min="1"
                    class="w-full px-3 py-2 bg-gray-700 border border-gray-600 text-white placeholder-gray-400 rounded-lg focus:ring-2 focus:ring-green-500">
                <p class="text-xs text-gray-400 mt-1">Example: set 12 for trays, 25 for sacks, 50 for pieces. Buyers can order 12/24/36... when bulk is enabled.</p>
            </div>
            <div class="md:col-span-2">
                <div class="flex items-center justify-between mb-1">
                    <label class="block text-sm font-medium text-gray-300">Consumer Quantity Choices (Optional)</label>
                    <button type="button" id="add_order_choice" class="px-3 py-1 text-xs rounded bg-sky-600 hover:bg-sky-500 text-white font-semibold">+ Add Choice</button>
                </div>
                <div class="mb-2 flex flex-wrap items-center gap-2">
                    <span class="text-xs text-gray-400">Quick add:</span>
                    <button type="button" class="quick-choice px-2 py-1 rounded border border-orange-400/70 text-xs text-orange-200 hover:bg-orange-500/20" data-choice="6">6</button>
                    <button type="button" class="quick-choice px-2 py-1 rounded border border-orange-400/70 text-xs text-orange-200 hover:bg-orange-500/20" data-choice="12">12</button>
                    <button type="button" class="quick-choice px-2 py-1 rounded border border-orange-400/70 text-xs text-orange-200 hover:bg-orange-500/20" data-choice="24">24</button>
                    <button type="button" class="quick-choice px-2 py-1 rounded border border-orange-400/70 text-xs text-orange-200 hover:bg-orange-500/20" data-choice="30">30</button>
                    <button type="button" class="quick-choice px-2 py-1 rounded border border-orange-400/70 text-xs text-orange-200 hover:bg-orange-500/20" data-choice="50">50</button>
                </div>
                <div class="mb-2 flex gap-2">
                    <input type="text" id="bulk_choice_input" placeholder="Type choices like 6,12,24"
                        class="w-full px-3 py-2 bg-gray-700 border border-gray-600 text-white placeholder-gray-400 rounded-lg focus:ring-2 focus:ring-green-500">
                    <button type="button" id="add_bulk_choices" class="px-3 py-2 text-xs rounded bg-indigo-600 hover:bg-indigo-500 text-white font-semibold">Add List</button>
                </div>
                <div id="order_choices_wrap" class="space-y-2"></div>
                <p class="text-xs text-gray-400 mt-1">If you add choices here, buyers must pick only from these options (web and app). Example: 6, 12, 24 trays.</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Discount (%)</label>
                <input type="number" name="discount_percentage" value="{{ old('discount_percentage', $product->discount_percentage) }}" step="0.01" min="0" max="100"
                    class="w-full px-3 py-2 bg-gray-700 border border-gray-600 text-white placeholder-gray-400 rounded-lg focus:ring-2 focus:ring-green-500">
            </div>
        </div>

        <!-- Status -->
        <h3 class="font-semibold text-lg mb-4 pb-2 border-b border-gray-600 text-white">Status</h3>
        <div class="mb-6">
            <select name="status"
                class="w-full px-3 py-2 bg-gray-700 border border-gray-600 text-white rounded-lg focus:ring-2 focus:ring-green-500">
                <option value="active" {{ old('status', $product->status) === 'active' ? 'selected' : '' }}>Active (Visible to customers)</option>
                <option value="inactive" {{ old('status', $product->status) === 'inactive' ? 'selected' : '' }}>Inactive (Hidden)</option>
                <option value="out_of_stock" {{ old('status', $product->status) === 'out_of_stock' ? 'selected' : '' }}>Out of Stock</option>
            </select>
        </div>

        <!-- Image -->
        <h3 class="font-semibold text-lg mb-4 pb-2 border-b border-gray-600 text-white">Product Image</h3>
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-300 mb-1">Image URL</label>
            <input type="url" name="image_url" value="{{ old('image_url', $product->image_url) }}"
                @if(($isAtProductLimit ?? false)) readonly @endif
                class="w-full px-3 py-2 bg-gray-700 border border-gray-600 text-white placeholder-gray-400 rounded-lg focus:ring-2 focus:ring-green-500">
            @if($product->image_url)
            <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="mt-2 w-24 h-24 object-cover rounded">
            @endif
        </div>

        <div class="flex gap-3">
            <button type="submit" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">Update Product</button>
            <a href="{{ route('products.show', $product) }}" class="px-6 py-2 bg-gray-600 text-gray-200 rounded-lg hover:bg-gray-700">Cancel</a>
        </div>
    </form>
</div>

<script>
    const bulkMode = document.getElementById('is_bulk_order_enabled');
    const stepWrap = document.getElementById('order_quantity_step_wrap');
    const stepInput = document.getElementById('order_quantity_step');
    const choicesWrap = document.getElementById('order_choices_wrap');
    const addChoiceBtn = document.getElementById('add_order_choice');
    const quickChoiceButtons = Array.from(document.querySelectorAll('.quick-choice'));
    const bulkChoiceInput = document.getElementById('bulk_choice_input');
    const addBulkChoicesBtn = document.getElementById('add_bulk_choices');
    const minimumOrderInput = document.getElementById('minimum_order');
    const minimumOrderHint = document.getElementById('minimum_order_hint');

    function createChoiceRow(value = '') {
        const parsed = parseInt(value, 10);
        if (!Number.isFinite(parsed) || parsed < 1) {
            return;
        }

        const existingValues = Array.from(choicesWrap.querySelectorAll('input[name="order_quantity_options[]"]'))
            .map((input) => parseInt(input.value || '0', 10))
            .filter((number) => Number.isFinite(number) && number > 0);

        if (existingValues.includes(parsed)) {
            return;
        }

        const row = document.createElement('div');
        row.className = 'flex items-center gap-2';

        const input = document.createElement('input');
        input.type = 'number';
        input.name = 'order_quantity_options[]';
        input.min = '1';
        input.value = parsed;
        input.placeholder = 'Enter quantity option';
        input.className = 'w-full px-3 py-2 bg-gray-700 border border-gray-600 text-white placeholder-gray-400 rounded-lg focus:ring-2 focus:ring-green-500';

        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.textContent = 'Remove';
        removeBtn.className = 'remove-choice px-3 py-2 rounded bg-red-700 hover:bg-red-600 text-white text-xs font-semibold';
        removeBtn.addEventListener('click', () => row.remove());

        row.appendChild(input);
        row.appendChild(removeBtn);
        choicesWrap.appendChild(row);

        syncMinimumOrderState();
    }

    function hasCustomChoices() {
        const choiceInputs = Array.from(choicesWrap.querySelectorAll('input[name="order_quantity_options[]"]'));
        return choiceInputs.some((input) => {
            const value = parseInt(input.value || '0', 10);
            return Number.isFinite(value) && value > 0;
        });
    }

    function syncMinimumOrderState() {
        const customChoicesExist = hasCustomChoices();

        if (!minimumOrderInput) {
            return;
        }

        if (customChoicesExist) {
            minimumOrderInput.value = '1';
            minimumOrderInput.readOnly = true;
            minimumOrderInput.classList.add('opacity-60', 'cursor-not-allowed');
            if (minimumOrderHint) {
                minimumOrderHint.textContent = 'Minimum order is auto-set to 1 because custom quantity choices are active.';
            }
        } else {
            minimumOrderInput.readOnly = false;
            minimumOrderInput.classList.remove('opacity-60', 'cursor-not-allowed');
            if (minimumOrderHint) {
                minimumOrderHint.textContent = 'Used when there are no custom quantity choices.';
            }
        }
    }

    function toggleBulkStepField() {
        const enabled = bulkMode.value === '1';
        stepWrap.style.display = enabled ? 'block' : 'none';
        stepInput.required = enabled;
        if (!enabled) {
            stepInput.value = '1';
        }
    }

    addChoiceBtn.addEventListener('click', () => createChoiceRow());

    for (const quickButton of quickChoiceButtons) {
        quickButton.addEventListener('click', () => {
            const value = parseInt(quickButton.dataset.choice || '0', 10);
            createChoiceRow(value);
        });
    }

    if (addBulkChoicesBtn && bulkChoiceInput) {
        addBulkChoicesBtn.addEventListener('click', () => {
            const values = String(bulkChoiceInput.value || '')
                .split(',')
                .map((text) => parseInt(text.trim(), 10))
                .filter((number) => Number.isFinite(number) && number > 0);

            for (const value of values) {
                createChoiceRow(value);
            }

            bulkChoiceInput.value = '';
        });
    }
    choicesWrap.addEventListener('input', syncMinimumOrderState);
    choicesWrap.addEventListener('click', (event) => {
        if (event.target instanceof HTMLElement && event.target.classList.contains('remove-choice')) {
            setTimeout(syncMinimumOrderState, 0);
        }
    });

    @php
        $initialChoices = old('order_quantity_options', $product->order_quantity_options ?? []);
    @endphp
    @foreach($initialChoices as $choice)
        createChoiceRow('{{ (int) $choice }}');
    @endforeach

    bulkMode.addEventListener('change', toggleBulkStepField);
    toggleBulkStepField();
    syncMinimumOrderState();
</script>
@endsection
