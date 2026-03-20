<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\FarmOwner;
use App\Models\IncomeRecord;
use App\Models\Notification;
use App\Models\User;
use App\Services\PayMongoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public function __construct(private readonly PayMongoService $paymongo)
    {
    }

    public function index()
    {
        $user = Auth::user();

        if ($user->role === 'farm_owner') {
            $farm_owner = $user->farmOwner;
            $orders = Order::where('farm_owner_id', $farm_owner->id)
                ->with('consumer')
                ->latest('created_at')
                ->paginate(20);
            return view('orders.farm-owner-list', compact('orders'));
        }

        $orders = Order::where('consumer_id', $user->id)
            ->with(['farmOwner.user', 'items.product'])
            ->latest('created_at')
            ->paginate(20);

        return view('orders.consumer-list', compact('orders'));
    }

    public function show(Order $order)
    {
        $user = Auth::user();

        if ($user->role === 'farm_owner') {
            if ($order->farm_owner_id !== $user->farmOwner->id) {
                abort(403);
            }
        } else {
            if ($order->consumer_id !== $user->id) {
                abort(403);
            }
        }

        return view('orders.show', compact('order'));
    }

    public function cart_add(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $product = Product::findOrFail($validated['product_id']);

        if ($error = $product->validateOrderQuantity((int) $validated['quantity'])) {
            return response()->json(['error' => $error], 422);
        }

        if ($product->quantity_available < $validated['quantity']) {
            return response()->json(['error' => 'Insufficient stock'], 400);
        }

        $cart = session()->get('cart', []);
        $cart_key = 'product_' . $product->id;

        if (isset($cart[$cart_key])) {
            $newQuantity = !empty($product->normalized_order_quantity_options)
                ? (int) $validated['quantity']
                : ((int) $cart[$cart_key]['quantity'] + (int) $validated['quantity']);

            if ((int) $product->quantity_available < $newQuantity) {
                return response()->json([
                    'error' => "Only {$product->quantity_available} item(s) available for {$product->name}.",
                ], 400);
            }

            if ($error = $product->validateOrderQuantity($newQuantity)) {
                return response()->json(['error' => $error], 422);
            }

            $cart[$cart_key]['quantity'] = $newQuantity;
            $cart[$cart_key]['unit'] = $product->unit;
            $cart[$cart_key]['minimum_order'] = (int) $product->minimum_order;
            $cart[$cart_key]['is_bulk_order_enabled'] = (bool) $product->is_bulk_order_enabled;
            $cart[$cart_key]['order_quantity_step'] = (int) ($product->order_quantity_step ?: 1);
            $cart[$cart_key]['order_quantity_options'] = $product->normalized_order_quantity_options;
        } else {
            $cart[$cart_key] = [
                'product_id' => $product->id,
                'farm_owner_id' => $product->farm_owner_id,
                'name' => $product->name,
                'price' => $product->price,
                'quantity' => $validated['quantity'],
                'unit' => $product->unit,
                'minimum_order' => (int) $product->minimum_order,
                'is_bulk_order_enabled' => (bool) $product->is_bulk_order_enabled,
                'order_quantity_step' => (int) ($product->order_quantity_step ?: 1),
                'order_quantity_options' => $product->normalized_order_quantity_options,
            ];
        }

        session()->put('cart', $cart);

        $cartCount = collect($cart)->sum(fn($item) => (int) ($item['quantity'] ?? 0));

        return response()->json(['success' => true, 'cart_count' => $cartCount]);
    }

    public function cart_update(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|integer',
            'quantity' => 'required|integer|min:1',
        ]);

        $cart = session()->get('cart', []);
        $cartKey = 'product_' . $validated['product_id'];

        if (!isset($cart[$cartKey])) {
            return redirect()->route('checkout')->with('error', 'Cart item not found.');
        }

        $product = Product::find($validated['product_id']);
        if (!$product) {
            unset($cart[$cartKey]);
            session()->put('cart', $cart);
            return redirect()->route('checkout')->with('error', 'Product is no longer available and was removed from cart.');
        }

        if ((int) $product->quantity_available < (int) $validated['quantity']) {
            return redirect()->route('checkout')->with('error', "Only {$product->quantity_available} item(s) available for {$product->name}.");
        }

        if ($error = $product->validateOrderQuantity((int) $validated['quantity'])) {
            return redirect()->route('checkout')->with('error', $error);
        }

        $cart[$cartKey]['quantity'] = (int) $validated['quantity'];
        $cart[$cartKey]['unit'] = $product->unit;
        $cart[$cartKey]['minimum_order'] = (int) $product->minimum_order;
        $cart[$cartKey]['is_bulk_order_enabled'] = (bool) $product->is_bulk_order_enabled;
        $cart[$cartKey]['order_quantity_step'] = (int) ($product->order_quantity_step ?: 1);
        $cart[$cartKey]['order_quantity_options'] = $product->normalized_order_quantity_options;
        session()->put('cart', $cart);

        return redirect()->route('checkout')->with('success', 'Cart quantity updated.');
    }

    public function cart_remove(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|integer',
        ]);

        $cart = session()->get('cart', []);
        $cartKey = 'product_' . $validated['product_id'];

        if (isset($cart[$cartKey])) {
            unset($cart[$cartKey]);
            session()->put('cart', $cart);
        }

        if (empty($cart)) {
            return redirect()->route('products.index')->with('success', 'Cart is now empty.');
        }

        return redirect()->route('checkout')->with('success', 'Item removed from cart.');
    }

    public function checkout()
    {
        $cart = session()->get('cart', []);

        if (empty($cart)) {
            return redirect()->route('products.index')->with('error', 'Cart is empty');
        }

        $total_amount = collect($cart)->sum(fn($item) => $item['price'] * $item['quantity']);

        return view('orders.checkout', compact('cart', 'total_amount'));
    }

    public function place_order(Request $request)
    {
        $user = Auth::user();
        $cart = session()->get('cart', []);

        if (empty($cart)) {
            return redirect()->route('products.index')->with('error', 'Cart is empty');
        }

        $validated = $request->validate([
            'delivery_type' => 'required|in:delivery,pickup',
            'delivery_address' => 'required_if:delivery_type,delivery|string|max:500',
            'delivery_city' => 'required_if:delivery_type,delivery|string|max:255',
            'delivery_province' => 'required_if:delivery_type,delivery|string|max:255',
            'delivery_postal_code' => 'nullable|string|max:10',
            'payment_method' => 'required|in:cod,gcash,paymaya',
        ]);

        try {
            $createdOrder = null;

            DB::transaction(function () use ($user, $cart, $validated, &$createdOrder) {
                $farm_owner_id = null;
                
                // Re-verify prices from database to prevent manipulation
                $productIds = collect($cart)->pluck('product_id');
                $products = Product::whereIn('id', $productIds)
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                foreach ($cart as $key => $item) {
                    $product = $products->get($item['product_id']);
                    
                    if (!$product) {
                        throw new \RuntimeException("Product '{$item['name']}' is no longer available.");
                    }

                    if ($product->quantity_available < $item['quantity']) {
                        throw new \RuntimeException("Insufficient stock for '{$product->name}'. Available: {$product->quantity_available}");
                    }

                    if ($error = $product->validateOrderQuantity((int) $item['quantity'])) {
                        throw new \RuntimeException($error);
                    }

                    if ($farm_owner_id === null) {
                        $farm_owner_id = $product->farm_owner_id;
                    }

                    if ($product->farm_owner_id !== $farm_owner_id) {
                        throw new \RuntimeException('All products must be from the same farm owner');
                    }

                    // Use verified price from DB
                    $cart[$key]['verified_price'] = $product->price;
                    $cart[$key]['unit'] = $product->unit;
                    $cart[$key]['minimum_order'] = (int) $product->minimum_order;
                    $cart[$key]['is_bulk_order_enabled'] = (bool) $product->is_bulk_order_enabled;
                    $cart[$key]['order_quantity_step'] = (int) ($product->order_quantity_step ?: 1);
                    $cart[$key]['order_quantity_options'] = $product->normalized_order_quantity_options;
                }

                $subtotal = collect($cart)->sum(fn($item) => $item['verified_price'] * $item['quantity']);
                $tax = $subtotal * 0.12;
                $total_amount = $subtotal + ($validated['delivery_type'] === 'delivery' ? 100 : 0) + $tax;

                $order = Order::create([
                    'order_number' => 'ORD-' . strtoupper(uniqid()),
                    'consumer_id' => $user->id,
                    'farm_owner_id' => $farm_owner_id,
                    'subtotal' => $subtotal,
                    'shipping_cost' => $validated['delivery_type'] === 'delivery' ? 100 : 0,
                    'tax' => $tax,
                    'discount' => 0,
                    'total_amount' => $total_amount,
                    'status' => 'pending',
                    'payment_status' => 'unpaid',
                    'payment_method' => $validated['payment_method'],
                    'delivery_type' => $validated['delivery_type'],
                    'delivery_address' => $validated['delivery_address'] ?? null,
                    'delivery_city' => $validated['delivery_city'] ?? null,
                    'delivery_province' => $validated['delivery_province'] ?? null,
                    'delivery_postal_code' => $validated['delivery_postal_code'] ?? null,
                    'item_count' => collect($cart)->sum(fn($item) => $item['quantity']),
                ]);

                foreach ($cart as $item) {
                    $product = $products->get($item['product_id']);

                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['verified_price'],
                        'total_price' => $item['verified_price'] * $item['quantity'],
                        'product_attributes' => json_encode(['name' => $product->name]),
                    ]);

                    // Decrement stock
                    $product->decrement('quantity_available', $item['quantity']);
                    $product->increment('quantity_sold', $item['quantity']);
                }

                // Keep finance ledger in sync with order lifecycle.
                $farmOwner = FarmOwner::select('id', 'user_id')->find($farm_owner_id);
                if ($farmOwner) {
                    IncomeRecord::updateOrCreate(
                        ['order_id' => $order->id],
                        [
                            'farm_owner_id' => $farmOwner->id,
                            'recorded_by' => $farmOwner->user_id,
                            'category' => $this->resolveIncomeCategory($products),
                            'description' => 'Order ' . $order->order_number . ' sales income',
                            'customer_name' => $user->name,
                            'customer_contact' => $user->phone,
                            'amount' => $subtotal,
                            'tax_amount' => $tax,
                            'discount_amount' => 0,
                            'income_date' => now()->toDateString(),
                            'payment_status' => $order->payment_status === 'paid' ? 'received' : 'pending',
                            'payment_method' => $this->mapOrderPaymentToIncomeMethod($order->payment_method),
                            'reference_number' => $order->order_number,
                            'notes' => 'Auto-generated from ecommerce order placement.',
                        ]
                    );

                    Cache::forget("farm_{$farmOwner->id}_income_stats");
                }

                Log::info('Order created', ['order_id' => $order->id, 'consumer_id' => $user->id, 'total' => $total_amount]);

                $createdOrder = $order;
            });

            if (
                $createdOrder instanceof Order
                && in_array((string) $validated['payment_method'], ['gcash', 'paymaya'], true)
            ) {
                $paymentPayload = $this->createOrderPayment(
                    $createdOrder->fresh(['items.product', 'farmOwner:id,farm_name']),
                    $user
                );

                if (!$paymentPayload || empty($paymentPayload['checkout_url'])) {
                    $this->rollbackCreatedOrder($createdOrder->id);
                    return redirect()->route('checkout')
                        ->withInput($request->except('_token'))
                        ->with('error', 'Unable to start PayMongo checkout right now. Please try again.');
                }

                session()->forget('cart');
                return redirect()->away($paymentPayload['checkout_url']);
            }

            session()->forget('cart');
            return redirect()->route('orders.index')->with('success', 'Order placed successfully');

        } catch (\RuntimeException $e) {
            // Business-rule violation thrown deliberately inside the transaction — safe to show.
            Log::warning('Order rejected', ['error' => $e->getMessage(), 'user_id' => $user->id]);
            return redirect()->back()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            Log::error('Order creation failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return redirect()->back()->with('error', 'We could not place your order right now. Please try again.');
        }
    }

    public function cancel_order(Order $order)
    {
        $user = Auth::user();

        if (!in_array((string) $user->role, ['consumer', 'client'], true) || (int) $order->consumer_id !== (int) $user->id) {
            abort(403);
        }

        if (!$order->canBeCancelled()) {
            return redirect()->back()->with('error', 'This order can no longer be cancelled.');
        }

        if ($order->payment_status === 'paid') {
            return redirect()->back()->with('error', 'Paid orders cannot be cancelled here until refund support is added.');
        }

        DB::transaction(function () use ($order, $user) {
            $order->loadMissing(['items.product', 'farmOwner:id,user_id,farm_name']);

            foreach ($order->items as $item) {
                if (!$item->product) {
                    continue;
                }

                $item->product->increment('quantity_available', (int) $item->quantity);

                if ((int) $item->product->quantity_sold > 0) {
                    $decrementBy = min((int) $item->quantity, (int) $item->product->quantity_sold);
                    $item->product->decrement('quantity_sold', $decrementBy);
                }
            }

            $order->update(['status' => 'cancelled']);

            Notification::create([
                'user_id' => $user->id,
                'title' => 'Order Cancelled',
                'message' => "Your order {$order->order_number} has been cancelled.",
                'type' => 'system',
                'channel' => 'in_app',
                'data' => [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                ],
                'status' => 'sent',
                'sent_at' => now(),
            ]);

            if ($order->farmOwner?->user_id) {
                Notification::create([
                    'user_id' => $order->farmOwner->user_id,
                    'title' => 'Order Cancelled by Customer',
                    'message' => "Order {$order->order_number} has been cancelled by {$user->name}.",
                    'type' => 'alert',
                    'channel' => 'in_app',
                    'data' => [
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                        'consumer_id' => $user->id,
                    ],
                    'status' => 'sent',
                    'sent_at' => now(),
                ]);
            }
        });

        return redirect()->route('orders.show', $order)->with('success', 'Order cancelled successfully.');
    }

    public function retry_payment(Order $order)
    {
        $user = Auth::user();

        if (!in_array((string) $user->role, ['consumer', 'client'], true) || (int) $order->consumer_id !== (int) $user->id) {
            abort(403);
        }

        if ($order->payment_status === 'paid') {
            return redirect()->back()->with('error', 'This order is already paid.');
        }

        if (!in_array((string) $order->payment_method, ['gcash', 'paymaya'], true)) {
            return redirect()->back()->with('error', 'Retry payment is only available for online payments.');
        }

        if (in_array((string) $order->status, ['cancelled', 'refunded'], true)) {
            return redirect()->back()->with('error', 'Cancelled or refunded orders cannot be paid again.');
        }

        $paymentPayload = $this->createOrderPayment($order->fresh(['items.product', 'farmOwner:id,farm_name']), $user);
        if (!$paymentPayload || empty($paymentPayload['checkout_url'])) {
            return redirect()->back()->with('error', 'Unable to start PayMongo checkout right now. Please try again.');
        }

        return redirect()->away($paymentPayload['checkout_url']);
    }

    public function confirm_order(Order $order, Request $request)
    {
        $user = Auth::user();

        if ($user->role !== 'farm_owner' || $order->farm_owner_id !== $user->farmOwner->id) {
            abort(403);
        }

        $activeSubscription = $user->farmOwner->subscriptions()
            ->where('status', 'active')
            ->where('ends_at', '>', now())
            ->first();

        if (!$activeSubscription) {
            return redirect()->route('farmowner.subscriptions')
                ->with('error', 'You need an active subscription to manage and confirm orders.');
        }

        if ($order->status !== 'pending') {
            return redirect()->back()->with('error', 'Order cannot be confirmed');
        }

        $order->update(['status' => 'confirmed']);

        return redirect()->back()->with('success', 'Order confirmed');
    }

    private function resolveIncomeCategory($products): string
    {
        $categories = $products->pluck('category')->filter()->all();

        if (in_array('eggs', $categories, true)) {
            return 'egg_sales';
        }

        if (in_array('feeds', $categories, true)) {
            return 'feed_sales';
        }

        if (count(array_intersect($categories, ['live_stock', 'breeding', 'fighting_cock'])) > 0) {
            return 'chicken_sales';
        }

        return 'product_sales';
    }

    private function mapOrderPaymentToIncomeMethod(?string $orderPaymentMethod): string
    {
        return match ((string) $orderPaymentMethod) {
            'gcash' => 'gcash',
            'paymaya' => 'maya',
            default => 'cash',
        };
    }

    private function createOrderPayment(Order $order, User $consumer): ?array
    {
        $order->loadMissing(['items.product', 'farmOwner:id,farm_name']);

        $paymentMethod = (string) $order->payment_method;
        $lineItems = $order->items
            ->map(function (OrderItem $item) {
                return [
                    'currency' => 'PHP',
                    'amount' => (int) round(((float) $item->unit_price) * 100),
                    'name' => $item->product?->name ?? ($item->product_attributes['name'] ?? 'Product'),
                    'quantity' => (int) $item->quantity,
                    'description' => 'Marketplace order item',
                ];
            })
            ->values()
            ->all();

        $lineItems[] = [
            'currency' => 'PHP',
            'amount' => (int) round(((float) $order->shipping_cost) * 100),
            'name' => 'Shipping Fee',
            'quantity' => 1,
            'description' => 'Delivery charge',
        ];

        if ((float) $order->tax > 0) {
            $lineItems[] = [
                'currency' => 'PHP',
                'amount' => (int) round(((float) $order->tax) * 100),
                'name' => 'VAT',
                'quantity' => 1,
                'description' => 'Tax charge',
            ];
        }

        $metadata = [
            'purpose' => 'marketplace_order',
            'order_id' => (string) $order->id,
            'consumer_id' => (string) $consumer->id,
            'farm_owner_id' => (string) $order->farm_owner_id,
            'payment_method' => $paymentMethod,
        ];

        $successUrl = route('orders.show', $order) . '?payment=success';
        $cancelUrl = route('orders.show', $order) . '?payment=cancelled';

        $checkoutData = $this->paymongo->createCheckoutSession([
            'amount' => (int) round(((float) $order->total_amount) * 100),
            'plan_name' => "Order {$order->order_number}",
            'description' => "Payment for marketplace order {$order->order_number}",
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'line_items' => $lineItems,
            'payment_method_types' => [$paymentMethod],
            'metadata' => $metadata,
        ]);

        $checkoutUrl = $checkoutData['attributes']['checkout_url'] ?? null;
        if ($checkoutData && $checkoutUrl) {
            $order->update([
                'paymongo_payment_id' => $checkoutData['id'],
            ]);

            return [
                'provider' => 'paymongo',
                'method' => $paymentMethod,
                'checkout_url' => $checkoutUrl,
                'paymongo_id' => $checkoutData['id'],
            ];
        }

        $remarks = implode('|', [
            'PURPOSE:MARKETPLACE_ORDER',
            'ORDER_ID:' . $order->id,
            'CONSUMER_ID:' . $consumer->id,
            'FARM_OWNER_ID:' . $order->farm_owner_id,
            'PAYMENT_METHOD:' . Str::upper($paymentMethod),
        ]);

        $linkData = $this->paymongo->createPaymentLink([
            'amount' => (int) round(((float) $order->total_amount) * 100),
            'description' => "Payment for marketplace order {$order->order_number}",
            'remarks' => $remarks,
        ]);

        $linkUrl = $linkData['attributes']['checkout_url'] ?? null;
        if (!$linkData || !$linkUrl) {
            return null;
        }

        $order->update([
            'paymongo_payment_id' => $linkData['id'],
        ]);

        return [
            'provider' => 'paymongo',
            'method' => $paymentMethod,
            'checkout_url' => $linkUrl,
            'paymongo_id' => $linkData['id'],
        ];
    }

    private function rollbackCreatedOrder(int $orderId): void
    {
        DB::transaction(function () use ($orderId) {
            $order = Order::with(['items.product'])->find($orderId);
            if (!$order) {
                return;
            }

            foreach ($order->items as $item) {
                if (!$item->product) {
                    continue;
                }

                $item->product->increment('quantity_available', (int) $item->quantity);

                if ((int) $item->product->quantity_sold > 0) {
                    $decrementBy = min((int) $item->quantity, (int) $item->product->quantity_sold);
                    $item->product->decrement('quantity_sold', $decrementBy);
                }
            }

            $order->items()->delete();
            $order->delete();
        });
    }
}
