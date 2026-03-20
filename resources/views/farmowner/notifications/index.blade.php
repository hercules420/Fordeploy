@extends('farmowner.layouts.app')

@section('title', 'Complaint Inbox')
@section('header', 'Complaint Inbox')
@section('subheader', 'Customer complaints and issue alerts from marketplace orders')

@section('header-actions')
<form method="POST" action="{{ route('farmowner.notifications.readAll') }}">
    @csrf
    <button type="submit" class="px-4 py-2 bg-gray-700 text-white rounded-lg hover:bg-gray-600">
        Mark All As Read
    </button>
</form>
@endsection

@section('content')
<div class="bg-gray-800 border border-gray-700 rounded-lg overflow-hidden">
    @if($notifications->count() > 0)
        <div class="divide-y divide-gray-700">
            @foreach($notifications as $notification)
                @php
                    $meta = is_array($notification->data) ? $notification->data : [];
                @endphp
                <div class="p-5 {{ $notification->is_read ? 'bg-gray-800' : 'bg-orange-900/10' }}">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm font-semibold {{ $notification->is_read ? 'text-gray-200' : 'text-orange-300' }}">
                                {{ $notification->title }}
                            </p>
                            <p class="mt-1 text-sm text-gray-300">{{ $notification->message }}</p>

                            <div class="mt-2 text-xs text-gray-400 space-y-1">
                                @if(!empty($meta['order_number']))
                                    <p>Order: {{ $meta['order_number'] }}</p>
                                @endif
                                @if(!empty($meta['consumer_name']))
                                    <p>Customer: {{ $meta['consumer_name'] }}</p>
                                @endif
                                <p>Received: {{ $notification->created_at->format('M d, Y h:i A') }}</p>
                            </div>
                        </div>

                        <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $notification->is_read ? 'bg-gray-700 text-gray-200' : 'bg-orange-600 text-white' }}">
                            {{ $notification->is_read ? 'Read' : 'Unread' }}
                        </span>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="p-4 border-t border-gray-700">
            {{ $notifications->links() }}
        </div>
    @else
        <div class="p-10 text-center text-gray-400">
            No complaint notifications yet.
        </div>
    @endif
</div>
@endsection
