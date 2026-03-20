<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - Poultry System</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-900 text-gray-100 min-h-screen">
    <div class="max-w-6xl mx-auto px-4 py-6">
        @include('marketplace.partials.navbar')

        <div class="flex justify-between items-center mb-4">
            <h1 class="text-2xl font-extrabold">My Orders</h1>
            <a href="{{ route('products.index') }}" class="px-3 py-2 rounded-lg border border-gray-700 hover:bg-gray-800">Back to Shop</a>
        </div>

        @if (session('success'))
            <div class="mb-4 rounded-lg border border-green-500/40 bg-green-900/30 p-3 text-green-200">{{ session('success') }}</div>
        @endif

        <div class="rounded-xl border border-gray-700 bg-gray-800 overflow-hidden shadow-sm">
            <table class="w-full text-sm">
            <thead class="bg-gray-900 text-gray-300">
                    <tr>
                        <th class="px-4 py-3 text-left">Order #</th>
                        <th class="px-4 py-3 text-left">Farm Owner</th>
                        <th class="px-4 py-3 text-left">Items</th>
                        <th class="px-4 py-3 text-left">Status</th>
                        <th class="px-4 py-3 text-left">Total</th>
                        <th class="px-4 py-3 text-left">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                        @php
                            $consumerStatusCode = (string) ($order->delivery?->status ?? $order->status);
                            $statusLabel = match ($consumerStatusCode) {
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
                                default => ucwords(str_replace('_', ' ', $consumerStatusCode)),
                            };
                        @endphp
                        <tr class="border-t border-gray-700">
                            <td class="px-4 py-3">{{ $order->order_number }}</td>
                            <td class="px-4 py-3">{{ $order->farmOwner?->farm_name ?? $order->farmOwner?->user?->name ?? 'Farm' }}</td>
                            <td class="px-4 py-3">{{ $order->item_count }}</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 rounded text-xs bg-gray-900 border border-gray-700">{{ $statusLabel }}</span>
                                <div class="text-xs text-gray-400 mt-1">
                                    {{ strtoupper((string) $order->payment_method ?: 'COD') }} | {{ ucfirst((string) $order->payment_status ?: 'unpaid') }}
                                </div>
                            </td>
                            <td class="px-4 py-3">PHP {{ number_format((float)$order->total_amount, 2) }}</td>
                            <td class="px-4 py-3">
                                <div class="flex flex-col gap-2 items-start">
                                    <a href="{{ route('orders.show', $order) }}" class="text-orange-300 hover:underline">View</a>

                                    @if($order->payment_status !== 'paid' && in_array((string) $order->status, ['pending', 'confirmed'], true))
                                        <form method="POST" action="{{ route('orders.cancel', $order) }}" onsubmit="return confirm('Cancel this order?')">
                                            @csrf
                                            <button type="submit" class="text-xs px-2 py-1 rounded bg-red-700/80 hover:bg-red-600">Cancel Order</button>
                                        </form>
                                    @endif

                                    @if(
                                        $order->payment_status !== 'paid'
                                        && in_array((string) $order->payment_method, ['gcash', 'paymaya'], true)
                                        && !in_array((string) $order->status, ['cancelled', 'refunded'], true)
                                    )
                                        <form method="POST" action="{{ route('orders.retry-payment', $order) }}">
                                            @csrf
                                            <button type="submit" class="text-xs px-2 py-1 rounded bg-emerald-700/80 hover:bg-emerald-600">Retry Payment</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-slate-500">No orders yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $orders->links() }}</div>
    </div>
</body>
</html>
