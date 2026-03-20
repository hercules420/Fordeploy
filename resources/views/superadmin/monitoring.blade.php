<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Operations Monitoring - Admin</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-900 text-gray-200">
    <div class="flex h-screen">
        <aside class="w-64 bg-gray-800 border-r border-gray-700">
            <div class="p-6 border-b border-gray-700">
                <h1 class="text-2xl font-bold text-orange-500">Poultry Admin</h1>
            </div>

            <nav class="p-4 space-y-2">
                <a href="{{ route('superadmin.dashboard') }}" class="block px-4 py-3 hover:bg-gray-700 rounded-lg">Dashboard</a>
                <a href="{{ route('superadmin.farm_owners') }}" class="block px-4 py-3 hover:bg-gray-700 rounded-lg">Farm Owners</a>
                <a href="{{ route('superadmin.orders') }}" class="block px-4 py-3 hover:bg-gray-700 rounded-lg">Orders</a>
                <a href="{{ route('superadmin.monitoring') }}" class="block px-4 py-3 bg-orange-600 text-white rounded-lg">Monitoring</a>
                <a href="{{ route('superadmin.subscriptions') }}" class="block px-4 py-3 hover:bg-gray-700 rounded-lg">Subscriptions</a>
                <a href="{{ route('superadmin.users') }}" class="block px-4 py-3 hover:bg-gray-700 rounded-lg">Users</a>
                <a href="{{ route('superadmin.support.index') }}" class="block px-4 py-3 hover:bg-gray-700 rounded-lg">Support</a>
                <hr class="my-4 border-gray-600">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full px-4 py-3 text-left hover:bg-red-600 rounded-lg">Logout</button>
                </form>
            </nav>
        </aside>

        <main class="flex-1 overflow-auto">
            <header class="bg-gray-800 border-b border-gray-700 px-8 py-4">
                <h2 class="text-2xl font-bold">Cross-Farm Monitoring</h2>
                <p class="text-gray-400 text-sm">Operational health across all farm owners and connected customer touchpoints</p>
            </header>

            <div class="p-8 space-y-8">
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                    <div class="bg-gray-800 p-4 rounded-lg border border-gray-700"><p class="text-gray-400 text-xs">Flocks</p><p class="text-2xl font-bold text-blue-400">{{ $stats['flocks_total'] }}</p></div>
                    <div class="bg-gray-800 p-4 rounded-lg border border-gray-700"><p class="text-gray-400 text-xs">Overdue Vaccinations</p><p class="text-2xl font-bold text-red-400">{{ $stats['vaccinations_overdue'] }}</p></div>
                    <div class="bg-gray-800 p-4 rounded-lg border border-gray-700"><p class="text-gray-400 text-xs">Low Stock Supplies</p><p class="text-2xl font-bold text-yellow-400">{{ $stats['supplies_low_stock'] }}</p></div>
                    <div class="bg-gray-800 p-4 rounded-lg border border-gray-700"><p class="text-gray-400 text-xs">Suppliers</p><p class="text-2xl font-bold text-indigo-400">{{ $stats['suppliers_total'] }}</p></div>
                    <div class="bg-gray-800 p-4 rounded-lg border border-gray-700"><p class="text-gray-400 text-xs">Active Drivers</p><p class="text-2xl font-bold text-cyan-400">{{ $stats['drivers_active'] }}</p></div>
                    <div class="bg-gray-800 p-4 rounded-lg border border-gray-700"><p class="text-gray-400 text-xs">Pending Deliveries</p><p class="text-2xl font-bold text-orange-400">{{ $stats['deliveries_pending'] }}</p></div>
                    <div class="bg-gray-800 p-4 rounded-lg border border-gray-700"><p class="text-gray-400 text-xs">Expenses This Month</p><p class="text-2xl font-bold text-rose-400">PHP {{ number_format($stats['expenses_this_month'], 2) }}</p></div>
                    <div class="bg-gray-800 p-4 rounded-lg border border-gray-700"><p class="text-gray-400 text-xs">Income This Month</p><p class="text-2xl font-bold text-green-400">PHP {{ number_format($stats['income_this_month'], 2) }}</p></div>
                    <div class="bg-gray-800 p-4 rounded-lg border border-gray-700"><p class="text-gray-400 text-xs">Open Support Tickets</p><p class="text-2xl font-bold text-fuchsia-400">{{ $stats['support_open'] }}</p></div>
                    <div class="bg-gray-800 p-4 rounded-lg border border-gray-700"><p class="text-gray-400 text-xs">Active Employees</p><p class="text-2xl font-bold text-sky-400">{{ $stats['employees_active'] }}</p></div>
                    <div class="bg-gray-800 p-4 rounded-lg border border-gray-700"><p class="text-gray-400 text-xs">Absent Today</p><p class="text-2xl font-bold text-amber-400">{{ $stats['attendance_absent_today'] }}</p></div>
                    <div class="bg-gray-800 p-4 rounded-lg border border-gray-700"><p class="text-gray-400 text-xs">Pending Payroll</p><p class="text-2xl font-bold text-violet-400">{{ $stats['payroll_pending'] }}</p></div>
                </div>

                <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
                    <section class="bg-gray-800 rounded-lg border border-gray-700 overflow-hidden">
                        <header class="px-4 py-3 border-b border-gray-700"><h3 class="font-bold">Flocks</h3></header>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="bg-gray-700"><tr><th class="text-left px-4 py-2">Farm</th><th class="text-left px-4 py-2">Batch</th><th class="text-left px-4 py-2">Count</th><th class="text-left px-4 py-2">Status</th></tr></thead>
                                <tbody class="divide-y divide-gray-700">
                                @forelse($flocks as $item)
                                    <tr>
                                        <td class="px-4 py-2">{{ $item->farmOwner?->farm_name ?? 'N/A' }}</td>
                                        <td class="px-4 py-2">{{ $item->batch_name }}</td>
                                        <td class="px-4 py-2">{{ $item->current_count }}</td>
                                        <td class="px-4 py-2">{{ ucfirst($item->status) }}</td>
                                    </tr>
                                @empty
                                    <tr><td class="px-4 py-3 text-gray-400" colspan="4">No records</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <section class="bg-gray-800 rounded-lg border border-gray-700 overflow-hidden">
                        <header class="px-4 py-3 border-b border-gray-700"><h3 class="font-bold">Vaccinations</h3></header>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="bg-gray-700"><tr><th class="text-left px-4 py-2">Farm</th><th class="text-left px-4 py-2">Vaccine</th><th class="text-left px-4 py-2">Due</th><th class="text-left px-4 py-2">Status</th></tr></thead>
                                <tbody class="divide-y divide-gray-700">
                                @forelse($vaccinations as $item)
                                    <tr>
                                        <td class="px-4 py-2">{{ $item->farmOwner?->farm_name ?? 'N/A' }}</td>
                                        <td class="px-4 py-2">{{ $item->name }}</td>
                                        <td class="px-4 py-2">{{ $item->next_due_date?->format('M d, Y') ?? 'N/A' }}</td>
                                        <td class="px-4 py-2">{{ ucfirst($item->status) }}</td>
                                    </tr>
                                @empty
                                    <tr><td class="px-4 py-3 text-gray-400" colspan="4">No records</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <section class="bg-gray-800 rounded-lg border border-gray-700 overflow-hidden">
                        <header class="px-4 py-3 border-b border-gray-700"><h3 class="font-bold">Supplies and Suppliers</h3></header>
                        <div class="p-4 space-y-3 text-sm border-b border-gray-700">
                            @forelse($supplies as $item)
                                <div class="flex justify-between gap-4">
                                    <div>
                                        <p class="font-semibold">{{ $item->name }}</p>
                                        <p class="text-gray-400">{{ $item->farmOwner?->farm_name ?? 'N/A' }} • {{ $item->supplier?->company_name ?? 'No Supplier' }}</p>
                                    </div>
                                    <p class="{{ $item->quantity_on_hand <= $item->reorder_point ? 'text-yellow-400 font-semibold' : 'text-gray-200' }}">{{ $item->quantity_on_hand }} qty</p>
                                </div>
                            @empty
                                <p class="text-gray-400">No supply records</p>
                            @endforelse
                        </div>
                        <div class="p-4 space-y-2 text-sm">
                            @forelse($suppliers as $item)
                                <div class="flex justify-between">
                                    <p>{{ $item->company_name }} <span class="text-gray-400">({{ $item->farmOwner?->farm_name ?? 'N/A' }})</span></p>
                                    <p class="text-gray-300">PHP {{ number_format((float) $item->outstanding_balance, 2) }}</p>
                                </div>
                            @empty
                                <p class="text-gray-400">No supplier records</p>
                            @endforelse
                        </div>
                    </section>

                    <section class="bg-gray-800 rounded-lg border border-gray-700 overflow-hidden">
                        <header class="px-4 py-3 border-b border-gray-700"><h3 class="font-bold">Drivers and Deliveries</h3></header>
                        <div class="p-4 space-y-3 text-sm border-b border-gray-700">
                            @forelse($drivers as $item)
                                <div class="flex justify-between">
                                    <p>{{ $item->name }} <span class="text-gray-400">• {{ $item->farmOwner?->farm_name ?? 'N/A' }}</span></p>
                                    <p class="text-gray-300">{{ ucfirst(str_replace('_', ' ', $item->status)) }}</p>
                                </div>
                            @empty
                                <p class="text-gray-400">No driver records</p>
                            @endforelse
                        </div>
                        <div class="p-4 space-y-3 text-sm">
                            @forelse($deliveries as $item)
                                <div>
                                    <p class="font-semibold">{{ $item->tracking_number }} • {{ ucfirst(str_replace('_', ' ', $item->status)) }}</p>
                                    <p class="text-gray-400">Farm: {{ $item->farmOwner?->farm_name ?? 'N/A' }} • Consumer: {{ $item->order?->consumer?->name ?? 'N/A' }} • Driver: {{ $item->driver?->name ?? 'Unassigned' }}</p>
                                </div>
                            @empty
                                <p class="text-gray-400">No delivery records</p>
                            @endforelse
                        </div>
                    </section>

                    <section class="bg-gray-800 rounded-lg border border-gray-700 overflow-hidden">
                        <header class="px-4 py-3 border-b border-gray-700"><h3 class="font-bold">Expenses and Income</h3></header>
                        <div class="p-4 space-y-3 text-sm border-b border-gray-700">
                            @forelse($expenses as $item)
                                <div class="flex justify-between">
                                    <p>{{ $item->expense_number }} <span class="text-gray-400">• {{ $item->farmOwner?->farm_name ?? 'N/A' }}</span></p>
                                    <p class="text-rose-300">PHP {{ number_format((float) $item->total_amount, 2) }}</p>
                                </div>
                            @empty
                                <p class="text-gray-400">No expense records</p>
                            @endforelse
                        </div>
                        <div class="p-4 space-y-3 text-sm">
                            @forelse($incomeRecords as $item)
                                <div class="flex justify-between">
                                    <p>{{ $item->income_number }} <span class="text-gray-400">• {{ $item->farmOwner?->farm_name ?? 'N/A' }}</span></p>
                                    <p class="text-green-300">PHP {{ number_format((float) $item->total_amount, 2) }}</p>
                                </div>
                            @empty
                                <p class="text-gray-400">No income records</p>
                            @endforelse
                        </div>
                    </section>

                    <section class="bg-gray-800 rounded-lg border border-gray-700 overflow-hidden">
                        <header class="px-4 py-3 border-b border-gray-700"><h3 class="font-bold">Support, Employees, Attendance, Payroll</h3></header>
                        <div class="p-4 space-y-3 text-sm border-b border-gray-700">
                            @forelse($supportTickets as $item)
                                <div>
                                    <p class="font-semibold">{{ $item->subject }} <span class="text-gray-400">({{ ucfirst($item->status) }})</span></p>
                                    <p class="text-gray-400">Farm: {{ $item->farmOwner?->farm_name ?? 'N/A' }} • Last sender: {{ $item->latestMessage?->sender?->name ?? strtoupper((string) ($item->latestMessage?->sender_role ?? 'N/A')) }}</p>
                                </div>
                            @empty
                                <p class="text-gray-400">No support tickets</p>
                            @endforelse
                        </div>
                        <div class="p-4 space-y-2 text-sm">
                            <p class="font-semibold text-gray-300">Employees</p>
                            @forelse($employees as $item)
                                <p>{{ trim($item->first_name . ' ' . $item->last_name) }} • {{ $item->farmOwner?->farm_name ?? 'N/A' }} • {{ ucfirst($item->status) }}</p>
                            @empty
                                <p class="text-gray-400">No employee records</p>
                            @endforelse
                            <p class="font-semibold text-gray-300 mt-3">Attendance</p>
                            @forelse($attendance as $item)
                                <p>{{ $item->employee?->first_name }} {{ $item->employee?->last_name }} • {{ $item->farmOwner?->farm_name ?? 'N/A' }} • {{ ucfirst(str_replace('_', ' ', $item->status)) }} • {{ $item->work_date?->format('M d, Y') }}</p>
                            @empty
                                <p class="text-gray-400">No attendance records</p>
                            @endforelse
                            <p class="font-semibold text-gray-300 mt-3">Payroll</p>
                            @forelse($payroll as $item)
                                <p>{{ $item->employee?->first_name }} {{ $item->employee?->last_name }} • {{ $item->farmOwner?->farm_name ?? 'N/A' }} • PHP {{ number_format((float) $item->net_pay, 2) }} • {{ ucfirst($item->status) }}</p>
                            @empty
                                <p class="text-gray-400">No payroll records</p>
                            @endforelse
                        </div>
                    </section>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
