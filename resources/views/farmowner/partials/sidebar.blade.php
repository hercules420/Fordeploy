<aside class="w-64 bg-gray-800 border-r border-gray-700 flex-shrink-0">
    <div class="p-6 border-b border-gray-700">
        <h1 class="text-2xl font-bold text-orange-500">Farm Portal</h1>
        <p class="text-gray-400 text-sm mt-1">{{ $farm_owner->farm_name ?? 'My Farm' }}</p>
    </div>
    
    <nav class="p-4 space-y-1 overflow-y-auto max-h-[calc(100vh-120px)]">
        <!-- Dashboard -->
        <a href="{{ route('farmowner.dashboard') }}" 
           class="block px-4 py-2.5 rounded-lg {{ request()->routeIs('farmowner.dashboard') ? 'bg-orange-600 text-white' : 'text-gray-300 hover:bg-gray-700' }}">
            <span class="mr-2">📊</span> Dashboard
        </a>

        <!-- Flock Management -->
        <div class="pt-4">
            <p class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">Flock Management</p>
        </div>
        <a href="{{ route('flocks.index') }}" 
           class="block px-4 py-2.5 rounded-lg {{ request()->routeIs('flocks.*') ? 'bg-orange-600 text-white' : 'text-gray-300 hover:bg-gray-700' }}">
            <span class="mr-2">🐔</span> Flocks
        </a>
        <a href="{{ route('vaccinations.index') }}" 
           class="block px-4 py-2.5 rounded-lg {{ request()->routeIs('vaccinations.*') ? 'bg-orange-600 text-white' : 'text-gray-300 hover:bg-gray-700' }}">
            <span class="mr-2">💉</span> Vaccinations
        </a>

        <!-- Inventory & Supply -->
        <div class="pt-4">
            <p class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">Inventory</p>
        </div>
        <a href="{{ route('supplies.index') }}" 
           class="block px-4 py-2.5 rounded-lg {{ request()->routeIs('supplies.*') ? 'bg-orange-600 text-white' : 'text-gray-300 hover:bg-gray-700' }}">
            <span class="mr-2">📦</span> Supplies
        </a>
        <a href="{{ route('suppliers.index') }}" 
           class="block px-4 py-2.5 rounded-lg {{ request()->routeIs('suppliers.*') ? 'bg-orange-600 text-white' : 'text-gray-300 hover:bg-gray-700' }}">
            <span class="mr-2">🏢</span> Suppliers
        </a>

        <!-- Sales & Orders -->
        <div class="pt-4">
            <p class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">Sales</p>
        </div>
        <a href="{{ route('products.index') }}" 
           class="block px-4 py-2.5 rounded-lg {{ request()->routeIs('products.*') ? 'bg-orange-600 text-white' : 'text-gray-300 hover:bg-gray-700' }}">
            <span class="mr-2">🏷️</span> Products
        </a>
        <a href="{{ route('orders.index') }}" 
           class="block px-4 py-2.5 rounded-lg {{ request()->routeIs('orders.*') ? 'bg-orange-600 text-white' : 'text-gray-300 hover:bg-gray-700' }}">
            <span class="mr-2">🛒</span> Orders
        </a>

        <!-- Logistics -->
        <div class="pt-4">
            <p class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">Logistics</p>
        </div>
        <a href="{{ route('drivers.index') }}" 
           class="block px-4 py-2.5 rounded-lg {{ request()->routeIs('drivers.*') ? 'bg-orange-600 text-white' : 'text-gray-300 hover:bg-gray-700' }}">
            <span class="mr-2">🚗</span> Drivers
        </a>
        <a href="{{ route('deliveries.index') }}" 
           class="block px-4 py-2.5 rounded-lg {{ request()->routeIs('deliveries.*') ? 'bg-orange-600 text-white' : 'text-gray-300 hover:bg-gray-700' }}">
            <span class="mr-2">📬</span> Deliveries
        </a>

        <!-- HR & Payroll -->
        <div class="pt-4">
            <p class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">HR & Payroll</p>
        </div>
        <a href="{{ route('employees.index') }}" 
           class="block px-4 py-2.5 rounded-lg {{ request()->routeIs('employees.*') ? 'bg-orange-600 text-white' : 'text-gray-300 hover:bg-gray-700' }}">
            <span class="mr-2">👥</span> Employees
        </a>
        <a href="{{ route('attendance.index') }}" 
           class="block px-4 py-2.5 rounded-lg {{ request()->routeIs('attendance.*') ? 'bg-orange-600 text-white' : 'text-gray-300 hover:bg-gray-700' }}">
            <span class="mr-2">⏰</span> Attendance
        </a>
        <a href="{{ route('payroll.index') }}" 
           class="block px-4 py-2.5 rounded-lg {{ request()->routeIs('payroll.*') ? 'bg-orange-600 text-white' : 'text-gray-300 hover:bg-gray-700' }}">
            <span class="mr-2">💵</span> Payroll
        </a>

        <!-- Finance -->
        <div class="pt-4">
            <p class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">Finance</p>
        </div>
        <a href="{{ route('expenses.index') }}" 
           class="block px-4 py-2.5 rounded-lg {{ request()->routeIs('expenses.*') ? 'bg-orange-600 text-white' : 'text-gray-300 hover:bg-gray-700' }}">
            <span class="mr-2">📉</span> Expenses
        </a>
        <a href="{{ route('income.index') }}" 
           class="block px-4 py-2.5 rounded-lg {{ request()->routeIs('income.*') ? 'bg-orange-600 text-white' : 'text-gray-300 hover:bg-gray-700' }}">
            <span class="mr-2">📈</span> Income
        </a>

        <!-- Reports -->
        <div class="pt-4">
            <p class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">Analytics</p>
        </div>
        <a href="{{ route('reports.dashboard') }}" 
           class="block px-4 py-2.5 rounded-lg {{ request()->routeIs('reports.dashboard') ? 'bg-orange-600 text-white' : 'text-gray-300 hover:bg-gray-700' }}">
            <span class="mr-2">📊</span> DSS Dashboard
        </a>
        <a href="{{ route('reports.index') }}" 
           class="block px-4 py-2.5 rounded-lg {{ request()->routeIs('reports.index') || request()->routeIs('reports.financial') || request()->routeIs('reports.production') ? 'bg-orange-600 text-white' : 'text-gray-300 hover:bg-gray-700' }}">
            <span class="mr-2">📋</span> Reports
        </a>

        <!-- Support -->
        <div class="pt-4">
            <p class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">Support</p>
        </div>
        <a href="{{ route('farmowner.support.index') }}"
           class="block px-4 py-2.5 rounded-lg {{ request()->routeIs('farmowner.support.*') ? 'bg-orange-600 text-white' : 'text-gray-300 hover:bg-gray-700' }}">
            <span class="mr-2">💬</span> Customer Support
        </a>
        <a href="{{ route('farmowner.notifications.index') }}"
           class="block px-4 py-2.5 rounded-lg {{ request()->routeIs('farmowner.notifications.*') ? 'bg-orange-600 text-white' : 'text-gray-300 hover:bg-gray-700' }}">
            <span class="mr-2">🔔</span> Complaint Inbox
        </a>

        <div class="pt-4">
            <p class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">Internal Communication</p>
        </div>
        <a href="{{ route('farmowner.contact.hr') }}"
           class="block px-4 py-2.5 rounded-lg {{ request()->routeIs('farmowner.contact.hr') ? 'bg-orange-600 text-white' : 'text-gray-300 hover:bg-gray-700' }}">
            <span class="mr-2">🧑‍💼</span> Contact HR
        </a>
        <a href="{{ route('farmowner.contact.finance') }}"
           class="block px-4 py-2.5 rounded-lg {{ request()->routeIs('farmowner.contact.finance') ? 'bg-orange-600 text-white' : 'text-gray-300 hover:bg-gray-700' }}">
            <span class="mr-2">💳</span> Contact Finance
        </a>

        <!-- Settings -->
        <div class="pt-4">
            <p class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">Settings</p>
        </div>
        <a href="{{ route('farmowner.subscriptions') }}" 
           class="block px-4 py-2.5 rounded-lg {{ request()->routeIs('farmowner.subscriptions') ? 'bg-orange-600 text-white' : 'text-gray-300 hover:bg-gray-700' }}">
            <span class="mr-2">💳</span> Subscription
        </a>
        <a href="{{ route('farmowner.profile') }}" 
           class="block px-4 py-2.5 rounded-lg {{ request()->routeIs('farmowner.profile') ? 'bg-orange-600 text-white' : 'text-gray-300 hover:bg-gray-700' }}">
            <span class="mr-2">⚙️</span> Profile
        </a>

        <hr class="my-4 border-gray-600">
        <form method="POST" action="{{ route('farmowner.logout') }}">
            @csrf
            <button type="submit" class="w-full px-4 py-2.5 text-left text-gray-300 hover:bg-red-600 hover:text-white rounded-lg">
                <span class="mr-2">🚪</span> Logout
            </button>
        </form>
    </nav>
</aside>
