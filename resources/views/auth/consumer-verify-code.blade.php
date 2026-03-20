<x-guest-layout>
    <div class="min-h-screen flex items-center justify-center bg-[#1a202c] p-6">
        <div class="w-full max-w-md bg-[#111827] border border-gray-700 rounded-3xl p-8 shadow-2xl">
            <h2 class="text-2xl font-black text-[#4fd1c5] uppercase tracking-widest text-center">Verify Email</h2>
            <p class="text-gray-400 text-sm text-center mt-2">Enter the 6-digit code sent to <span class="text-white">{{ $email }}</span>.</p>

            @if (session('success'))
                <div class="mt-4 rounded-xl border border-green-500/40 bg-green-500/10 p-3 text-green-200 text-sm">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mt-4 rounded-xl border border-red-500/40 bg-red-500/10 p-3 text-red-200 text-sm">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('consumer.verify.submit') }}" class="mt-6 space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-black uppercase text-gray-400 mb-2">Verification Code</label>
                    <input type="text" name="code" maxlength="6" inputmode="numeric" placeholder="123456" required class="w-full rounded-xl bg-[#1a202c] border border-gray-700 text-white px-4 py-3 tracking-[0.35em] text-center">
                </div>

                <button type="submit" class="w-full bg-[#4fd1c5] hover:bg-[#38b2ac] text-[#111827] font-black py-3 rounded-xl uppercase tracking-widest">
                    Verify and Continue
                </button>
            </form>

            <form method="POST" action="{{ route('consumer.verify.resend') }}" class="mt-4">
                @csrf
                <button type="submit" class="w-full border border-gray-600 text-gray-200 font-bold py-3 rounded-xl hover:bg-gray-700/40">
                    Resend Code
                </button>
            </form>
        </div>
    </div>
</x-guest-layout>
