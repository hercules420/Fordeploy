<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Ratings - Marketplace</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-900 text-gray-100">
    <div class="mx-auto max-w-6xl px-4 py-6">
        @include('marketplace.partials.navbar')

        <div class="rounded-2xl border border-gray-700 bg-gray-800 p-5">
            <h1 class="text-2xl font-extrabold">Rate Delivered Orders</h1>
            <p class="mt-1 text-sm text-gray-400">Rate your overall delivery and farm service from 1 to 5 stars.</p>

            @if(session('success'))
                <div class="mt-4 rounded-lg border border-green-600/40 bg-green-900/30 p-3 text-green-200">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="mt-4 rounded-lg border border-red-600/40 bg-red-900/30 p-3 text-red-200">{{ session('error') }}</div>
            @endif

            <div class="mt-5 space-y-4">
                @forelse($deliveries as $delivery)
                    <div class="rounded-xl border border-gray-700 bg-gray-900/50 p-4">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div>
                                <p class="font-semibold text-orange-300">Order {{ $delivery->order?->order_number }}</p>
                                <p class="text-sm text-gray-400">
                                    Farm: {{ $delivery->order?->farmOwner?->farm_name ?? 'Farm' }}
                                    | Delivered: {{ $delivery->delivered_at?->format('M d, Y') ?? 'N/A' }}
                                </p>
                            </div>
                            <span class="rounded-full border border-green-700 bg-green-900/30 px-2 py-1 text-xs text-green-300">Delivered</span>
                        </div>

                        <form method="POST" action="{{ route('marketplace.ratings.store', $delivery) }}" class="mt-3 grid gap-3 md:grid-cols-4">
                            @csrf
                            <div>
                                <label class="mb-1 block text-xs text-gray-400">Rating (1-5)</label>
                                <select name="rating" required class="w-full rounded-lg border border-gray-600 bg-white px-3 py-2 text-black">
                                    @for($i = 5; $i >= 1; $i--)
                                        <option value="{{ $i }}" {{ (int) old('rating', (int) ($delivery->rating ?? 0)) === $i ? 'selected' : '' }}>
                                            {{ $i }} Star{{ $i > 1 ? 's' : '' }}
                                        </option>
                                    @endfor
                                </select>
                            </div>
                            <div class="md:col-span-2">
                                <label class="mb-1 block text-xs text-gray-400">Feedback (optional)</label>
                                <input type="text" name="feedback" value="{{ old('feedback', $delivery->feedback) }}" class="w-full rounded-lg border border-gray-600 bg-white px-3 py-2 text-black" placeholder="Share your experience">
                            </div>
                            <div class="flex items-end">
                                <button type="submit" class="w-full rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700">
                                    {{ $delivery->rating ? 'Update Rating' : 'Submit Rating' }}
                                </button>
                            </div>
                        </form>
                    </div>
                @empty
                    <div class="rounded-lg border border-dashed border-gray-700 p-8 text-center text-gray-500">
                        No delivered orders available for rating yet.
                    </div>
                @endforelse
            </div>

            <div class="mt-5">{{ $deliveries->links() }}</div>
        </div>
    </div>
</body>
</html>
