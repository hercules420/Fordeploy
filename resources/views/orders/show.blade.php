<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order {{ $order->order_number }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-900 text-gray-100 min-h-screen">
    <div class="max-w-4xl mx-auto px-4 py-6">
        @if(auth()->check() && in_array((string) auth()->user()->role, ['consumer', 'client'], true))
            @include('marketplace.partials.navbar')
        @endif

        <a href="{{ route('orders.index') }}" class="text-orange-300 hover:underline text-sm">&larr; Back to orders</a>

        <div class="mt-3 rounded-xl border border-gray-700 bg-gray-800 p-5 shadow-sm">
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
            <h1 class="text-xl font-extrabold">Order {{ $order->order_number }}</h1>
            <p class="text-sm text-gray-300 mt-1">Status: {{ $statusLabel }} | Payment: {{ ucfirst($order->payment_status) }} | Method: {{ strtoupper((string) $order->payment_method ?: 'COD') }}</p>

            @if (session('success'))
                <div class="mt-3 rounded-lg border border-green-500/40 bg-green-900/30 p-3 text-green-200 text-sm">{{ session('success') }}</div>
            @endif

            @if (session('error'))
                <div class="mt-3 rounded-lg border border-red-500/40 bg-red-900/30 p-3 text-red-200 text-sm">{{ session('error') }}</div>
            @endif

            @if(auth()->check() && in_array((string) auth()->user()->role, ['consumer', 'client'], true))
                <div class="mt-4 flex flex-wrap gap-2">
                    @if($order->payment_status !== 'paid' && in_array((string) $order->status, ['pending', 'confirmed'], true))
                        <form method="POST" action="{{ route('orders.cancel', $order) }}" onsubmit="return confirm('Cancel this order?')">
                            @csrf
                            <button type="submit" class="px-3 py-2 rounded bg-red-700/80 hover:bg-red-600 text-sm">Cancel Order</button>
                        </form>
                    @endif

                    @if(
                        $order->payment_status !== 'paid'
                        && in_array((string) $order->payment_method, ['gcash', 'paymaya'], true)
                        && !in_array((string) $order->status, ['cancelled', 'refunded'], true)
                    )
                        <form method="POST" action="{{ route('orders.retry-payment', $order) }}">
                            @csrf
                            <button type="submit" class="px-3 py-2 rounded bg-emerald-700/80 hover:bg-emerald-600 text-sm">Retry Online Payment</button>
                        </form>
                    @endif
                </div>
            @endif

            <div class="mt-4 grid md:grid-cols-2 gap-4 text-sm">
                <div class="rounded-lg border border-gray-700 p-3">
                    <p class="text-gray-300">Farm Owner</p>
                    <p class="font-semibold">{{ $order->farmOwner?->farm_name ?? $order->farmOwner?->user?->name ?? 'Farm' }}</p>
                </div>
                <div class="rounded-lg border border-gray-700 p-3">
                    <p class="text-gray-300">Delivery Type</p>
                    <p class="font-semibold">{{ ucfirst($order->delivery_type) }}</p>
                </div>
            </div>

            <div class="mt-4 rounded-lg border border-gray-700 overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-900 text-gray-300">
                        <tr>
                            <th class="px-4 py-3 text-left">Product</th>
                            <th class="px-4 py-3 text-left">Qty</th>
                            <th class="px-4 py-3 text-left">Unit Price</th>
                            <th class="px-4 py-3 text-left">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($order->items as $item)
                            <tr class="border-t border-gray-700">
                                <td class="px-4 py-3">{{ $item->product?->name ?? 'Product' }}</td>
                                <td class="px-4 py-3">{{ $item->quantity }}</td>
                                <td class="px-4 py-3">PHP {{ number_format((float)$item->unit_price, 2) }}</td>
                                <td class="px-4 py-3">PHP {{ number_format((float)$item->total_price, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4 text-right font-bold text-orange-300">Grand Total: PHP {{ number_format((float)$order->total_amount, 2) }}</div>
        </div>
    </div>
</body>
</html>
