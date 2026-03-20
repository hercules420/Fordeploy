@extends($layout)

@section('title', $pageTitle)
@section('header', $header)
@section('subheader', $subheader)

@section('sidebar-links')
@if(($currentRole ?? null) === 'finance')
    <a href="{{ route('department.finance.dashboard') }}"
       class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium text-gray-300 hover:bg-gray-700 hover:text-white">
        🏠 Dashboard
    </a>
    <a href="{{ route('expenses.index') }}"
       class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium text-gray-300 hover:bg-gray-700 hover:text-white">
        💸 Expenses
    </a>
    <a href="{{ route('income.index') }}"
       class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium text-gray-300 hover:bg-gray-700 hover:text-white">
        💰 Income
    </a>
    <a href="{{ route('payroll.index') }}"
       class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium text-gray-300 hover:bg-gray-700 hover:text-white">
        👔 Payroll
    </a>
    <a href="{{ route('department.messages') }}"
       class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium bg-orange-500/20 text-orange-400 border border-orange-500/30">
        💬 Communication
    </a>
@endif
@endsection

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 bg-gray-800 border border-gray-700 rounded-lg overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-700">
            <h3 class="text-base font-semibold text-white">Conversation History</h3>
        </div>

        <div class="divide-y divide-gray-700">
            @forelse($messages as $msg)
            <div class="p-4">
                <div class="flex items-center justify-between gap-2 mb-1">
                    <p class="text-sm font-semibold text-white">{{ $msg->subject }}</p>
                    <span class="text-xs text-gray-400">{{ $msg->created_at?->format('M d, Y h:i A') }}</span>
                </div>
                <p class="text-xs text-gray-400 mb-2">
                    From: {{ strtoupper(str_replace('_', ' ', $msg->sender_role)) }}
                    · To: {{ strtoupper(str_replace('_', ' ', $msg->recipient_role)) }}
                    · Type: {{ strtoupper(str_replace('_', ' ', $msg->message_type)) }}
                </p>
                <p class="text-sm text-gray-200 whitespace-pre-line">{{ $msg->message }}</p>
            </div>
            @empty
            <div class="p-6 text-sm text-gray-400">No messages found yet.</div>
            @endforelse
        </div>

        @if($messages->hasPages())
            <div class="p-4 border-t border-gray-700">{{ $messages->links() }}</div>
        @endif
    </div>

    <div class="bg-gray-800 border border-gray-700 rounded-lg p-5">
        <h3 class="text-base font-semibold text-white mb-4">Send Message</h3>

        <form method="POST" action="{{ route('communication.store') }}" class="space-y-3">
            @csrf
            <div>
                <label class="block text-xs text-gray-400 mb-1">To</label>
                <select name="recipient_role" class="w-full rounded-lg border border-gray-600 bg-gray-700 px-3 py-2 text-white">
                    @foreach(['farm_owner' => 'Farm Owner', 'hr' => 'HR', 'finance' => 'Finance'] as $roleValue => $roleLabel)
                        <option value="{{ $roleValue }}" {{ $recipientRole === $roleValue ? 'selected' : '' }}>{{ $roleLabel }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs text-gray-400 mb-1">Type</label>
                <select name="message_type" class="w-full rounded-lg border border-gray-600 bg-gray-700 px-3 py-2 text-white">
                    @foreach($allowedTypes as $type)
                        <option value="{{ $type }}">{{ ucfirst(str_replace('_', ' ', $type)) }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs text-gray-400 mb-1">Subject</label>
                <input type="text" name="subject" value="{{ old('subject') }}" class="w-full rounded-lg border border-gray-600 bg-gray-700 px-3 py-2 text-white" required>
            </div>

            <div>
                <label class="block text-xs text-gray-400 mb-1">Message</label>
                <textarea name="message" rows="5" class="w-full rounded-lg border border-gray-600 bg-gray-700 px-3 py-2 text-white" required>{{ old('message') }}</textarea>
            </div>

            <button type="submit" class="w-full rounded-lg bg-orange-600 px-4 py-2 text-white hover:bg-orange-700">Send</button>
        </form>
    </div>
</div>
@endsection
