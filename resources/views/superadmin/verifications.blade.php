<x-app-layout>
    <div class="p-8 bg-[#1a202c] min-h-screen text-white">
        <div class="flex justify-between items-center mb-8 border-b border-gray-700 pb-4">
            <h2 class="text-2xl font-bold text-[#ed8936] uppercase tracking-wider">
                Pending Farm Verifications
            </h2>
            <span class="bg-gray-800 text-[#4fd1c5] px-4 py-1 rounded-full text-xs font-bold">
                {{ $requests->count() }} Request(s) Found
            </span>
        </div>
        
        <div class="bg-[#111827] rounded-xl border border-gray-700 overflow-hidden shadow-2xl">
            <table class="w-full text-left">
                <thead class="bg-gray-800/50 text-gray-400 text-xs uppercase font-black">
                    <tr>
                        <th class="p-5">Farm & Owner</th>
                        <th class="p-5">Location</th>
                        <th class="p-5">Documents</th>
                        <th class="p-5">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-800">
                    @forelse($requests as $request)
                        <tr class="hover:bg-gray-800/30 transition">
                            <td class="p-5">
                                <div class="text-[#4fd1c5] font-bold text-lg">{{ $request->farm_name }}</div>
                                <div class="text-gray-500 text-xs italic">Owner: {{ $request->owner_name }}</div>
                            </td>
                            <td class="p-5 text-gray-400 text-sm">
                                <div class="max-w-xs truncate" title="{{ $request->farm_location }}">
                                    {{ $request->farm_location }}
                                </div>
                            </td>
                            <td class="p-5">
                                @php
                                    $validIdPath = str_replace('\\\\', '/', trim((string) $request->valid_id_path));
                                    if (\Illuminate\Support\Str::startsWith($validIdPath, 'storage/')) {
                                        $validIdPath = \Illuminate\Support\Str::after($validIdPath, 'storage/');
                                    }
                                    if (\Illuminate\Support\Str::startsWith($validIdPath, 'public/')) {
                                        $validIdPath = \Illuminate\Support\Str::after($validIdPath, 'public/');
                                    }
                                    $validIdUrl = \Illuminate\Support\Str::startsWith($validIdPath, ['http://', 'https://'])
                                        ? $validIdPath
                                        : \Illuminate\Support\Facades\Storage::disk('public')->url($validIdPath);

                                    $permitPath = str_replace('\\\\', '/', trim((string) $request->business_permit_path));
                                    if (\Illuminate\Support\Str::startsWith($permitPath, 'storage/')) {
                                        $permitPath = \Illuminate\Support\Str::after($permitPath, 'storage/');
                                    }
                                    if (\Illuminate\Support\Str::startsWith($permitPath, 'public/')) {
                                        $permitPath = \Illuminate\Support\Str::after($permitPath, 'public/');
                                    }
                                    $permitUrl = \Illuminate\Support\Str::startsWith($permitPath, ['http://', 'https://'])
                                        ? $permitPath
                                        : \Illuminate\Support\Facades\Storage::disk('public')->url($permitPath);
                                @endphp
                                <div class="flex flex-col gap-2">
                                    <a href="{{ $validIdUrl }}" target="_blank" 
                                       class="text-[#ed8936] hover:text-[#f6ad55] text-xs font-bold flex items-center gap-1 uppercase">
                                        📄 View Valid ID
                                    </a>
                                    <a href="{{ $permitUrl }}" target="_blank" 
                                       class="text-[#ed8936] hover:text-[#f6ad55] text-xs font-bold flex items-center gap-1 uppercase">
                                        📄 View Permit
                                    </a>
                                </div>
                            </td>
                            <td class="p-5">
    <div class="flex gap-2">
        <form action="{{ route('admin.verifications.approve', $request->id) }}" method="POST">
            @csrf
            <button type="submit" class="bg-green-600 hover:bg-green-500 text-white px-4 py-2 rounded-lg text-xs font-black shadow-lg">
                APPROVE
            </button>
        </form>

        <form action="{{ route('admin.verifications.reject', $request->id) }}" method="POST">
            @csrf
            <button type="submit" class="bg-red-600 hover:bg-red-500 text-white px-4 py-2 rounded-lg text-xs font-black shadow-lg">
                REJECT
            </button>
        </form>
    </div>
</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="p-20 text-center">
                                <div class="flex flex-col items-center">
                                    <svg class="w-16 h-16 text-gray-700 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <p class="text-gray-500 text-lg font-medium italic">No pending farm registrations at the moment.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>