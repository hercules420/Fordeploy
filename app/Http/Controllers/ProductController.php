<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\FarmOwner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        
        if ($user->role === 'farm_owner') {
            $farm_owner = $user->farmOwner;
            if (!$farm_owner) {
                return redirect()->route('farmowner.register');
            }
            $products = $farm_owner->products()->latest()->paginate(20);
            return view('farmowner.products.index', compact('products'));
        }

        $selectedFarmOwnerId = $request->integer('farm_owner_id');
        $searchTerm = trim((string) $request->input('q', ''));

        $products = Product::with('farmOwner.user')
            ->where('status', 'active')
            ->where('quantity_available', '>', 0)
            ->whereHas('farmOwner', function ($query) {
                $query->where('permit_status', 'approved');
            })
            ->when($selectedFarmOwnerId > 0, function ($query) use ($selectedFarmOwnerId) {
                $query->where('farm_owner_id', $selectedFarmOwnerId);
            })
            ->when($searchTerm !== '', function ($query) use ($searchTerm) {
                $query->where(function ($inner) use ($searchTerm) {
                    $inner->where('name', 'like', '%' . $searchTerm . '%')
                        ->orWhere('description', 'like', '%' . $searchTerm . '%')
                        ->orWhere('category', 'like', '%' . $searchTerm . '%');
                });
            })
            ->latest('published_at')
            ->paginate(20);

        $farmOwners = FarmOwner::query()
            ->where('permit_status', 'approved')
            ->whereHas('products', function ($query) {
                $query->where('status', 'active')->where('quantity_available', '>', 0);
            })
            ->orderBy('farm_name')
            ->get(['id', 'farm_name']);

        return view('products.browse', compact('products', 'farmOwners', 'selectedFarmOwnerId', 'searchTerm'));
    }

    public function create()
    {
        $this->authorize_farm_owner();

        $user = Auth::user();
        $farm_owner = $user->farmOwner;

        $active_sub = $this->activeSubscriptionOrRedirect($farm_owner);
        if (!$active_sub) {
            return redirect()->route('farmowner.subscriptions')
                ->with('error', 'You need an active subscription to manage products. Please subscribe to a plan first.');
        }

        // Check product limit
        if ($active_sub->product_limit) {
            $current_count = $farm_owner->products()->count();
            if ($current_count >= $active_sub->product_limit) {
                return redirect()->route('products.index')
                    ->with('error', "You've reached the maximum of {$active_sub->product_limit} products for your " . ucfirst($active_sub->plan_type) . " plan. Please upgrade your subscription to add more products.");
            }
        }

        return view('farmowner.products.create');
    }

    public function store(Request $request)
    {
        $this->authorize_farm_owner();

        $user = Auth::user();
        $farm_owner = $user->farmOwner;

        $active_sub = $this->activeSubscriptionOrRedirect($farm_owner);
        if (!$active_sub) {
            return redirect()->route('farmowner.subscriptions')
                ->with('error', 'You need an active subscription to manage products. Please subscribe to a plan first.');
        }

        // Check product limit
        if ($active_sub->product_limit) {
            $current_count = $farm_owner->products()->count();
            if ($current_count >= $active_sub->product_limit) {
                return redirect()->route('products.index')
                    ->with('error', "You've reached the maximum of {$active_sub->product_limit} products for your " . ucfirst($active_sub->plan_type) . " plan. Please upgrade your subscription to add more products.");
            }
        }

        $validated = $request->validate([
            'sku' => 'required|string|unique:products|max:100',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|in:live_stock,breeding,fighting_cock,eggs,feeds,equipment,other',
            'quantity_available' => 'required|integer|min:0',
            'price' => 'required|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'unit' => 'required|string|max:50',
            'minimum_order' => 'integer|min:1',
            'is_bulk_order_enabled' => 'required|boolean',
            'order_quantity_step' => 'nullable|integer|min:1',
            'order_quantity_options' => 'nullable|array',
            'order_quantity_options.*' => 'nullable|integer|min:1',
            'discount_percentage' => 'numeric|min:0|max:100',
            'image_url' => 'nullable|url',
        ]);

        $validated['is_bulk_order_enabled'] = (bool) $validated['is_bulk_order_enabled'];
        $validated['order_quantity_step'] = $validated['is_bulk_order_enabled']
            ? max(1, (int) ($validated['order_quantity_step'] ?? 1))
            : 1;
        $validated['order_quantity_options'] = $this->normalizeOrderQuantityOptions(
            $validated['order_quantity_options'] ?? []
        );
        if (!empty($validated['order_quantity_options'])) {
            $validated['minimum_order'] = 1;
        }

        $product = Product::create([
            'farm_owner_id' => $farm_owner->id,
            ...$validated,
            'status' => 'active',
            'published_at' => now(),
        ]);

        Log::info('Product created', ['product_id' => $product->id, 'farm_owner_id' => $farm_owner->id]);

        return redirect()->route('products.show', $product)->with('success', 'Product created successfully');
    }

    public function show(Product $product)
    {
        $user = Auth::user();
        if ($user && $user->role === 'farm_owner' && $product->farm_owner_id === $user->farmOwner?->id) {
            return view('farmowner.products.show', compact('product'));
        }
        return view('products.show', compact('product'));
    }

    public function edit(Product $product)
    {
        $this->authorize_farm_owner();

        $farm_owner = Auth::user()->farmOwner;
        $active_sub = $this->activeSubscriptionOrRedirect($farm_owner);
        if (!$active_sub) {
            return redirect()->route('farmowner.subscriptions')
                ->with('error', 'You need an active subscription to manage products. Please subscribe to a plan first.');
        }
        
        if ($product->farm_owner_id !== $farm_owner->id) {
            abort(403);
        }

        $isAtProductLimit = (bool) ($active_sub->product_limit && $farm_owner->products()->count() >= $active_sub->product_limit);

        return view('farmowner.products.edit', compact('product', 'isAtProductLimit'));
    }

    public function update(Request $request, Product $product)
    {
        $this->authorize_farm_owner();

        $farm_owner = Auth::user()->farmOwner;
        $active_sub = $this->activeSubscriptionOrRedirect($farm_owner);
        if (!$active_sub) {
            return redirect()->route('farmowner.subscriptions')
                ->with('error', 'You need an active subscription to manage products. Please subscribe to a plan first.');
        }

        if ($product->farm_owner_id !== $farm_owner->id) {
            abort(403);
        }

        $validated = $request->validate([
            'category' => 'required|in:live_stock,breeding,fighting_cock,eggs,feeds,equipment,other',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive,out_of_stock',
            'quantity_available' => 'required|integer|min:0',
            'price' => 'required|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'unit' => 'required|string|max:50',
            'minimum_order' => 'integer|min:1',
            'is_bulk_order_enabled' => 'nullable|boolean',
            'order_quantity_step' => 'nullable|integer|min:1',
            'order_quantity_options' => 'nullable|array',
            'order_quantity_options.*' => 'nullable|integer|min:1',
            'discount_percentage' => 'numeric|min:0|max:100',
            'image_url' => 'nullable|url',
        ]);

        if (array_key_exists('is_bulk_order_enabled', $validated)) {
            $validated['is_bulk_order_enabled'] = (bool) $validated['is_bulk_order_enabled'];

            if ($validated['is_bulk_order_enabled']) {
                $validated['order_quantity_step'] = max(1, (int) ($validated['order_quantity_step'] ?? $product->order_quantity_step ?? 1));
            } else {
                $validated['order_quantity_step'] = 1;
            }
        } elseif (array_key_exists('order_quantity_step', $validated)) {
            $validated['order_quantity_step'] = max(1, (int) $validated['order_quantity_step']);
        }

        if (array_key_exists('order_quantity_options', $validated)) {
            $validated['order_quantity_options'] = $this->normalizeOrderQuantityOptions(
                $validated['order_quantity_options'] ?? []
            );

            if (!empty($validated['order_quantity_options'])) {
                $validated['minimum_order'] = 1;
            }
        }

        $isAtProductLimit = (bool) ($active_sub->product_limit && $farm_owner->products()->count() >= $active_sub->product_limit);
        if ($isAtProductLimit) {
            $identityChanged = false;

            if (array_key_exists('name', $validated) && trim((string) $validated['name']) !== (string) $product->name) {
                $identityChanged = true;
            }

            if (array_key_exists('description', $validated)) {
                $incomingDescription = trim((string) ($validated['description'] ?? ''));
                $currentDescription = trim((string) ($product->description ?? ''));
                if ($incomingDescription !== $currentDescription) {
                    $identityChanged = true;
                }
            }

            if (array_key_exists('image_url', $validated) && ($validated['image_url'] ?? null) !== $product->image_url) {
                $identityChanged = true;
            }

            if ($request->filled('sku') && trim((string) $request->input('sku')) !== (string) $product->sku) {
                $identityChanged = true;
            }

            if ((string) ($validated['category'] ?? '') !== (string) $product->category) {
                $identityChanged = true;
            }

            if ($identityChanged) {
                return redirect()->back()->withInput()->with(
                    'error',
                    'You have reached your product limit. You can only update stock, pricing, and status for existing products. Delete a product or upgrade your subscription to add a different product.'
                );
            }
        }

        $product->update($validated);

        return redirect()->back()->with('success', 'Product updated');
    }

    public function delete(Product $product)
    {
        $this->authorize_farm_owner();

        $farm_owner = Auth::user()->farmOwner;
        if (!$this->activeSubscriptionOrRedirect($farm_owner)) {
            return redirect()->route('farmowner.subscriptions')
                ->with('error', 'You need an active subscription to manage products. Please subscribe to a plan first.');
        }

        if ($product->farm_owner_id !== $farm_owner->id) {
            abort(403);
        }

        $product->delete();

        return redirect()->route('products.index')->with('success', 'Product deleted');
    }

    public function update_stock(Request $request, Product $product)
    {
        $this->authorize_farm_owner();

        $farm_owner = Auth::user()->farmOwner;
        if (!$this->activeSubscriptionOrRedirect($farm_owner)) {
            return redirect()->route('farmowner.subscriptions')
                ->with('error', 'You need an active subscription to manage products. Please subscribe to a plan first.');
        }

        if ($product->farm_owner_id !== $farm_owner->id) {
            abort(403);
        }

        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
            'action' => 'required|in:add,subtract',
        ]);

        $quantity = (int) $validated['quantity'];

        if ($validated['action'] === 'add') {
            $product->increment('quantity_available', $quantity);
            $message = "Added {$quantity} units to stock. New stock: {$product->fresh()->quantity_available}";
        } else {
            if ($product->quantity_available < $quantity) {
                return redirect()->back()->with('error', "Cannot subtract {$quantity} units. Current stock is only {$product->quantity_available}.");
            }
            $product->decrement('quantity_available', $quantity);
            $message = "Subtracted {$quantity} units from stock. New stock: {$product->fresh()->quantity_available}";
        }

        Log::info('Stock updated', [
            'product_id' => $product->id,
            'action' => $validated['action'],
            'quantity' => $quantity,
            'new_stock' => $product->fresh()->quantity_available,
            'farm_owner_id' => Auth::user()->farmOwner->id,
        ]);

        return redirect()->back()->with('success', $message);
    }

    private function authorize_farm_owner()
    {
        $user = Auth::user();
        if ($user->role !== 'farm_owner' || !$user->farmOwner) {
            abort(403);
        }
    }

    private function activeSubscriptionOrRedirect(FarmOwner $farm_owner)
    {
        return $farm_owner->subscriptions()->active()->first();
    }

    private function normalizeOrderQuantityOptions(array $rawOptions): array
    {
        return collect($rawOptions)
            ->map(fn($value) => (int) $value)
            ->filter(fn($value) => $value > 0)
            ->unique()
            ->sort()
            ->values()
            ->all();
    }
}
