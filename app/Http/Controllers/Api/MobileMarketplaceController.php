<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Delivery;
use App\Models\Notification;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Services\PayMongoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MobileMarketplaceController extends Controller
{
    public function __construct(private readonly PayMongoService $paymongo)
    {
    }

    public function profile(Request $request): JsonResponse
    {
        $consumer = $this->resolveConsumer($request);

        return response()->json([
            'data' => [
                'id' => $consumer->id,
                'name' => $consumer->name,
                'email' => $consumer->email,
                'phone' => $consumer->phone,
                'location' => $consumer->location,
                'role' => $consumer->role,
            ],
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $consumer = $this->resolveConsumer($request);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:30',
            'location' => 'nullable|string|max:255',
        ]);

        $consumer->update($validated);

        return response()->json([
            'message' => 'Profile updated successfully.',
            'data' => [
                'id' => $consumer->id,
                'name' => $consumer->name,
                'email' => $consumer->email,
                'phone' => $consumer->phone,
                'location' => $consumer->location,
                'role' => $consumer->role,
            ],
        ]);
    }

    public function orders(Request $request): JsonResponse
    {
        $consumer = $this->resolveConsumer($request);

        $orders = Order::where('consumer_id', $consumer->id)
            ->with(['farmOwner:id,farm_name', 'items.product:id,name', 'delivery:id,order_id,status'])
            ->latest('created_at')
            ->limit(50)
            ->get()
            ->map(function (Order $order) {
                $consumerStatusCode = $this->resolveConsumerOrderStatusCode($order);
                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'farm_name' => $order->farmOwner?->farm_name ?? 'Farm',
                    'status' => $order->status,
                    'consumer_status' => $consumerStatusCode,
                    'consumer_status_label' => $this->statusLabel($consumerStatusCode),
                    'payment_status' => $order->payment_status,
                    'payment_method' => $order->payment_method,
                    'delivery_address' => $order->delivery_address,
                    'total_amount' => (float) $order->total_amount,
                    'item_count' => (int) $order->item_count,
                    'can_cancel' => $order->canBeCancelled() && $order->payment_status !== 'paid',
                    'can_retry_payment' => $order->payment_status !== 'paid'
                        && in_array((string) $order->payment_method, ['gcash', 'paymaya'], true)
                        && !in_array((string) $order->status, ['cancelled', 'refunded'], true),
                    'created_at' => $order->created_at?->toIso8601String(),
                    'items' => $order->items->map(function (OrderItem $item) {
                        return [
                            'product_name' => $item->product?->name ?? 'Product',
                            'quantity' => (int) $item->quantity,
                            'unit_price' => (float) $item->unit_price,
                            'total_price' => (float) $item->total_price,
                        ];
                    })->values(),
                ];
            })
            ->values();

        return response()->json(['data' => $orders]);
    }

    private function resolveConsumerOrderStatusCode(Order $order): string
    {
        $deliveryStatus = (string) ($order->delivery?->status ?? '');

        return match ($deliveryStatus) {
            'preparing' => 'preparing',
            'packed' => 'packed',
            'assigned' => 'assigned',
            'out_for_delivery' => 'out_for_delivery',
            'delivered' => 'delivered',
            'completed' => 'completed',
            default => (string) $order->status,
        };
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'pending' => 'Pending Confirmation',
            'confirmed' => 'Confirmed',
            'processing', 'preparing' => 'Preparing',
            'ready_for_pickup', 'packed' => 'Packed',
            'assigned' => 'Driver Assigned',
            'out_for_delivery', 'shipped' => 'Out for Delivery',
            'delivered' => 'Delivered',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            'refunded' => 'Refunded',
            default => ucwords(str_replace('_', ' ', $status)),
        };
    }

    public function placeOrder(Request $request): JsonResponse
    {
        $consumer = $this->resolveConsumer($request);

        $validated = $request->validate([
            'delivery_address' => 'required|string|max:500',
            'delivery_city' => 'nullable|string|max:255',
            'delivery_province' => 'nullable|string|max:255',
            'delivery_postal_code' => 'nullable|string|max:20',
            'payment_method' => 'required|in:cod,gcash,paymaya',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        $createdOrder = DB::transaction(function () use ($validated, $consumer) {
            $items = collect($validated['items']);
            $products = Product::whereIn('id', $items->pluck('product_id'))
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $farmOwnerId = null;
            foreach ($items as $line) {
                $product = $products->get((int) $line['product_id']);

                if (!$product) {
                    abort(422, 'A selected product is no longer available.');
                }

                if ($product->status !== 'active') {
                    abort(422, "{$product->name} is no longer available.");
                }

                if ($product->farmOwner?->permit_status !== 'approved') {
                    abort(422, "Orders from this farm are not currently accepted.");
                }

                if ($product->quantity_available < (int) $line['quantity']) {
                    abort(422, "Insufficient stock for {$product->name}.");
                }

                $quantityError = $product->validateOrderQuantity((int) $line['quantity']);
                if ($quantityError) {
                    abort(422, $quantityError);
                }

                if ($farmOwnerId === null) {
                    $farmOwnerId = $product->farm_owner_id;
                }

                if ($farmOwnerId !== $product->farm_owner_id) {
                    abort(422, 'Please order from one farm at a time.');
                }
            }

            $subtotal = 0;
            $itemCount = 0;
            foreach ($items as $line) {
                $product = $products->get((int) $line['product_id']);
                $qty = (int) $line['quantity'];
                $subtotal += ((float) $product->price) * $qty;
                $itemCount += $qty;
            }

            $shipping = 100;
            $tax = $subtotal * 0.12;
            $total = $subtotal + $shipping + $tax;

            $order = Order::create([
                'order_number' => 'ORD-' . strtoupper(uniqid()),
                'consumer_id' => $consumer->id,
                'farm_owner_id' => $farmOwnerId,
                'subtotal' => $subtotal,
                'shipping_cost' => $shipping,
                'tax' => $tax,
                'discount' => 0,
                'total_amount' => $total,
                'status' => 'pending',
                'payment_status' => 'unpaid',
                'payment_method' => $validated['payment_method'],
                'delivery_type' => 'delivery',
                'delivery_address' => $validated['delivery_address'],
                'delivery_city' => $validated['delivery_city'] ?? null,
                'delivery_province' => $validated['delivery_province'] ?? null,
                'delivery_postal_code' => $validated['delivery_postal_code'] ?? null,
                'item_count' => $itemCount,
            ]);

            foreach ($items as $line) {
                $product = $products->get((int) $line['product_id']);
                $qty = (int) $line['quantity'];
                $unitPrice = (float) $product->price;

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'total_price' => $unitPrice * $qty,
                    'product_attributes' => json_encode(['name' => $product->name]),
                ]);

                $product->decrement('quantity_available', $qty);
                $product->increment('quantity_sold', $qty);
            }

            return $order;
        });

        $paymentPayload = null;
        if (in_array($validated['payment_method'], ['gcash', 'paymaya'], true)) {
            $paymentPayload = $this->createOrderPayment($createdOrder->fresh(['items.product', 'farmOwner']), $consumer);

            if (!$paymentPayload) {
                $this->rollbackCreatedOrder($createdOrder->id);

                return response()->json([
                    'message' => 'Unable to start the PayMongo checkout session right now. Please try again.',
                ], 502);
            }
        }

        return response()->json([
            'message' => 'Order placed successfully.',
            'data' => [
                'order_id' => $createdOrder->id,
                'order_number' => $createdOrder->order_number,
                'status' => $createdOrder->status,
                'payment_status' => $createdOrder->payment_status,
                'payment_method' => $createdOrder->payment_method,
                'payment' => $paymentPayload,
            ],
        ], 201);
    }

    public function cancelOrder(Request $request, Order $order): JsonResponse
    {
        $consumer = $this->resolveConsumer($request);

        if ($order->consumer_id !== $consumer->id) {
            abort(404);
        }

        if (!$order->canBeCancelled()) {
            return response()->json([
                'message' => 'This order can no longer be cancelled.',
            ], 422);
        }

        if ($order->payment_status === 'paid') {
            return response()->json([
                'message' => 'Paid orders cannot be cancelled from the app until refund support is added.',
            ], 422);
        }

        DB::transaction(function () use ($order, $consumer) {
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

            $order->update([
                'status' => 'cancelled',
            ]);

            Notification::create([
                'user_id' => $consumer->id,
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
                    'message' => "Order {$order->order_number} has been cancelled by {$consumer->name}.",
                    'type' => 'alert',
                    'channel' => 'in_app',
                    'data' => [
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                        'consumer_id' => $consumer->id,
                    ],
                    'status' => 'sent',
                    'sent_at' => now(),
                ]);
            }
        });

        return response()->json([
            'message' => 'Order cancelled successfully.',
        ]);
    }

    public function retryPayment(Request $request, Order $order): JsonResponse
    {
        $consumer = $this->resolveConsumer($request);

        if ($order->consumer_id !== $consumer->id) {
            abort(404);
        }

        if ($order->payment_status === 'paid') {
            return response()->json([
                'message' => 'This order is already paid.',
            ], 422);
        }

        if (!in_array((string) $order->payment_method, ['gcash', 'paymaya'], true)) {
            return response()->json([
                'message' => 'Retry payment is only available for online payments.',
            ], 422);
        }

        if (in_array((string) $order->status, ['cancelled', 'refunded'], true)) {
            return response()->json([
                'message' => 'Cancelled or refunded orders cannot be paid again.',
            ], 422);
        }

        $paymentPayload = $this->createOrderPayment($order->fresh(['items.product', 'farmOwner']), $consumer);
        if (!$paymentPayload) {
            return response()->json([
                'message' => 'Unable to start the PayMongo checkout session right now. Please try again.',
            ], 502);
        }

        return response()->json([
            'message' => 'Payment checkout created successfully.',
            'data' => [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'payment' => $paymentPayload,
            ],
        ]);
    }

    public function notifications(Request $request): JsonResponse
    {
        $consumer = $this->resolveConsumer($request);

        $notifications = Notification::forUser($consumer->id)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(fn(Notification $notification) => [
                'id' => $notification->id,
                'title' => $notification->title,
                'message' => $notification->message,
                'type' => $notification->type,
                'is_read' => (bool) $notification->is_read,
                'created_at' => $notification->created_at?->toIso8601String(),
            ])
            ->values();

        Notification::forUser($consumer->id)
            ->unread()
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return response()->json(['data' => $notifications]);
    }

    public function submitComplaint(Request $request): JsonResponse
    {
        $consumer = $this->resolveConsumer($request);

        $validated = $request->validate([
            'order_id' => 'required|integer|exists:orders,id',
            'subject' => 'required|string|max:120',
            'message' => 'required|string|max:1500',
        ]);

        $order = Order::where('id', $validated['order_id'])
            ->where('consumer_id', $consumer->id)
            ->with('farmOwner:id,user_id,farm_name')
            ->firstOrFail();

        if (!$order->farmOwner || !$order->farmOwner->user_id) {
            return response()->json([
                'message' => 'Farm owner account is not available for this order.',
            ], 422);
        }

        Notification::create([
            'user_id' => $order->farmOwner->user_id,
            'title' => 'Customer Complaint: ' . $validated['subject'],
            'message' => "Order {$order->order_number}: {$validated['message']}",
            'type' => 'alert',
            'channel' => 'in_app',
            'data' => [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'consumer_id' => $consumer->id,
                'consumer_name' => $consumer->name,
                'complaint_subject' => $validated['subject'],
            ],
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        Notification::create([
            'user_id' => $consumer->id,
            'title' => 'Complaint Sent',
            'message' => "Your complaint for order {$order->order_number} was sent to {$order->farmOwner->farm_name}.",
            'type' => 'system',
            'channel' => 'in_app',
            'data' => [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
            ],
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        return response()->json([
            'message' => 'Complaint sent to farm owner successfully.',
        ]);
    }

    public function ratings(Request $request): JsonResponse
    {
        $consumer = $this->resolveConsumer($request);

        $deliveries = Delivery::where('status', 'delivered')
            ->whereHas('order', function ($query) use ($consumer) {
                $query->where('consumer_id', $consumer->id);
            })
            ->with(['order:id,order_number,consumer_id,farm_owner_id', 'order.farmOwner:id,farm_name'])
            ->latest('delivered_at')
            ->limit(50)
            ->get()
            ->map(fn(Delivery $delivery) => [
                'id' => $delivery->id,
                'order_number' => $delivery->order?->order_number,
                'farm_name' => $delivery->order?->farmOwner?->farm_name ?? 'Farm',
                'delivered_at' => $delivery->delivered_at?->toIso8601String(),
                'rating' => $delivery->rating !== null ? (float) $delivery->rating : null,
                'feedback' => $delivery->feedback,
            ])
            ->values();

        return response()->json(['data' => $deliveries]);
    }

    public function submitRating(Request $request, Delivery $delivery): JsonResponse
    {
        $consumer = $this->resolveConsumer($request);

        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'feedback' => 'nullable|string|max:500',
        ]);

        $delivery->load('order', 'farmOwner');

        if (!$delivery->order || $delivery->order->consumer_id !== $consumer->id) {
            abort(403, 'You can only rate your own delivered orders.');
        }

        if ($delivery->status !== 'delivered') {
            return response()->json([
                'message' => 'Only delivered orders can be rated.',
            ], 422);
        }

        $delivery->rateDelivery((float) $validated['rating'], $validated['feedback'] ?? null);

        if ($delivery->farmOwner) {
            $average = Delivery::where('farm_owner_id', $delivery->farm_owner_id)
                ->whereNotNull('rating')
                ->avg('rating');

            $delivery->farmOwner->update([
                'average_rating' => round((float) ($average ?? 0), 2),
            ]);
        }

        return response()->json([
            'message' => 'Thank you! Your rating was submitted.',
        ]);
    }

    private function resolveConsumer(Request $request): User
    {
        $consumer = $request->user();

        if (!$consumer instanceof User) {
            abort(401, 'Authentication token is required.');
        }

        if (!in_array((string) $consumer->role, ['consumer', 'client'], true)) {
            abort(403, 'This endpoint is only available for marketplace consumers.');
        }

        return $consumer;
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

        $checkoutData = $this->paymongo->createCheckoutSession([
            'amount' => (int) round(((float) $order->total_amount) * 100),
            'plan_name' => "Order {$order->order_number}",
            'description' => "Payment for marketplace order {$order->order_number}",
            'success_url' => route('consumer.app.launch', ['status' => 'paid', 'order' => $order->order_number]),
            'cancel_url' => route('consumer.app.launch', ['status' => 'cancelled', 'order' => $order->order_number]),
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
