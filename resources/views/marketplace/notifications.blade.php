<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications and Complaints - Marketplace</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-900 text-gray-100">
    <div class="mx-auto max-w-6xl px-4 py-6">
        @include('marketplace.partials.navbar')

        <div class="grid gap-5 lg:grid-cols-2">
            <div class="rounded-2xl border border-gray-700 bg-gray-800 p-5">
                <h1 class="text-xl font-extrabold">Send Product Complaint</h1>
                <p class="mt-1 text-sm text-gray-400">This will notify the farm owner directly.</p>

                @if(session('success'))
                    <div class="mt-3 rounded-lg border border-green-600/40 bg-green-900/30 p-3 text-green-200">{{ session('success') }}</div>
                @endif
                @if(session('error'))
                    <div class="mt-3 rounded-lg border border-red-600/40 bg-red-900/30 p-3 text-red-200">{{ session('error') }}</div>
                @endif

                <form method="POST" action="{{ route('marketplace.complaints.store') }}" class="mt-4 space-y-3">
                    @csrf
                    <div>
                        <label class="mb-1 block text-sm text-gray-300">Order</label>
                        <select name="order_id" required class="w-full rounded-lg border border-gray-600 bg-white px-3 py-2 text-black">
                            <option value="">Select order</option>
                            @foreach($orders as $order)
                                <option value="{{ $order->id }}" {{ old('order_id') == $order->id ? 'selected' : '' }}>
                                    {{ $order->order_number }} - {{ $order->farmOwner?->farm_name ?? 'Farm' }} ({{ ucfirst($order->status) }})
                                </option>
                            @endforeach
                        </select>
                        @error('order_id')<p class="mt-1 text-xs text-red-400">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="mb-1 block text-sm text-gray-300">Subject</label>
                        <input type="text" name="subject" value="{{ old('subject') }}" required class="w-full rounded-lg border border-gray-600 bg-white px-3 py-2 text-black">
                        @error('subject')<p class="mt-1 text-xs text-red-400">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="mb-1 block text-sm text-gray-300">Message</label>
                        <textarea name="message" rows="4" required class="w-full rounded-lg border border-gray-600 bg-white px-3 py-2 text-black">{{ old('message') }}</textarea>
                        @error('message')<p class="mt-1 text-xs text-red-400">{{ $message }}</p>@enderror
                    </div>

                    <button type="submit" class="rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700">Send Complaint</button>
                </form>
            </div>

            <div class="rounded-2xl border border-gray-700 bg-gray-800 p-5">
                <h2 class="text-xl font-extrabold">Notification Inbox</h2>
                <p class="mt-1 text-sm text-gray-400">Recent updates and complaint acknowledgements.</p>

                <div class="mt-4 space-y-3">
                    @forelse($notifications as $notification)
                        <div class="rounded-lg border border-gray-700 bg-gray-900/50 p-3">
                            <div class="flex items-center justify-between gap-2">
                                <p class="font-semibold text-orange-300">{{ $notification->title }}</p>
                                <span class="text-xs text-gray-500">{{ $notification->created_at?->diffForHumans() }}</span>
                            </div>
                            <p class="mt-1 text-sm text-gray-300">{{ $notification->message }}</p>
                        </div>
                    @empty
                        <div class="rounded-lg border border-dashed border-gray-700 p-6 text-center text-sm text-gray-500">
                            No notifications yet.
                        </div>
                    @endforelse
                </div>

                <div class="mt-4">{{ $notifications->links() }}</div>
            </div>
        </div>
    </div>
</body>
</html>
