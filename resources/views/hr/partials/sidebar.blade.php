<aside class="w-64 flex-shrink-0 border-r border-gray-700 bg-gray-800">
    <div class="border-b border-gray-700 p-6">
        <h1 class="text-2xl font-bold text-orange-500">HR Portal</h1>
        <p class="mt-1 text-sm text-gray-400">{{ Auth::user()->name }}</p>
    </div>

    <nav class="max-h-[calc(100vh-120px)] space-y-1 overflow-y-auto p-4">
        <a href="{{ route('hr.users.index') }}"
           class="block rounded-lg px-4 py-2.5 {{ request()->routeIs('hr.users.*') ? 'bg-orange-600 text-white' : 'text-gray-300 hover:bg-gray-700' }}">
            <span class="mr-2">🧭</span> HR Home
        </a>

        <div class="pt-4">
            <p class="px-4 text-xs font-semibold uppercase tracking-wider text-gray-400">Workforce</p>
        </div>

        <a href="{{ route('employees.index') }}"
           class="block rounded-lg px-4 py-2.5 {{ request()->routeIs('employees.*') ? 'bg-orange-600 text-white' : 'text-gray-300 hover:bg-gray-700' }}">
            <span class="mr-2">👥</span> Employees
        </a>

        <a href="{{ route('attendance.index') }}"
           class="block rounded-lg px-4 py-2.5 {{ request()->routeIs('attendance.*') ? 'bg-orange-600 text-white' : 'text-gray-300 hover:bg-gray-700' }}">
            <span class="mr-2">⏱️</span> Attendance
        </a>

        <a href="{{ route('payroll.index') }}"
           class="block rounded-lg px-4 py-2.5 {{ request()->routeIs('payroll.*') ? 'bg-orange-600 text-white' : 'text-gray-300 hover:bg-gray-700' }}">
            <span class="mr-2">💵</span> Payroll Prep
        </a>

        <div class="pt-4">
            <p class="px-4 text-xs font-semibold uppercase tracking-wider text-gray-400">Communication</p>
        </div>

        <a href="{{ route('department.messages', ['to' => 'finance']) }}"
           class="block rounded-lg px-4 py-2.5 {{ request()->routeIs('department.messages') && request('to') === 'finance' ? 'bg-orange-600 text-white' : 'text-gray-300 hover:bg-gray-700' }}">
            <span class="mr-2">💬</span> Contact Finance
        </a>

        <a href="{{ route('department.messages', ['to' => 'farm_owner']) }}"
           class="block rounded-lg px-4 py-2.5 {{ request()->routeIs('department.messages') && request('to') === 'farm_owner' ? 'bg-orange-600 text-white' : 'text-gray-300 hover:bg-gray-700' }}">
            <span class="mr-2">👨‍🌾</span> Contact Farm Owner
        </a>

        <div class="pt-4">
            <p class="px-4 text-xs font-semibold uppercase tracking-wider text-gray-400">Access Control</p>
        </div>

        <a href="{{ route('hr.users.index') }}"
           class="block rounded-lg px-4 py-2.5 {{ request()->routeIs('hr.users.*') ? 'bg-orange-600 text-white' : 'text-gray-300 hover:bg-gray-700' }}">
            <span class="mr-2">🔐</span> Department Users
        </a>

        <div class="pt-4">
            <p class="px-4 text-xs font-semibold uppercase tracking-wider text-gray-400">Account</p>
        </div>

        <a href="{{ route('profile.edit') }}"
           class="block rounded-lg px-4 py-2.5 {{ request()->routeIs('profile.*') ? 'bg-orange-600 text-white' : 'text-gray-300 hover:bg-gray-700' }}">
            <span class="mr-2">⚙️</span> Profile
        </a>

        <hr class="my-4 border-gray-700">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="w-full rounded-lg px-4 py-2.5 text-left text-gray-300 hover:bg-red-600 hover:text-white">
                <span class="mr-2">🚪</span> Logout
            </button>
        </form>
    </nav>
</aside>