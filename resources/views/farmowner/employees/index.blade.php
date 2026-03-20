@extends(auth()->user()?->isHR() ? 'hr.layouts.app' : 'farmowner.layouts.app')

@section('title', 'Employees')
@section('header', 'Employee Management')
@section('subheader', 'Manage farm workers and staff')

@section('header-actions')
<a href="{{ route('employees.create') }}" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">+ Add Employee</a>
@endsection

@section('content')
<!-- Stats -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="bg-gray-800 border border-gray-700 rounded-lg p-4 border-l-4 border-blue-600">
        <p class="text-gray-400 text-xs">Total Employees</p>
        <p class="text-2xl font-bold text-blue-600">{{ $stats['total'] ?? 0 }}</p>
    </div>
    <div class="bg-gray-800 border border-gray-700 rounded-lg p-4 border-l-4 border-green-600">
        <p class="text-gray-400 text-xs">Active</p>
        <p class="text-2xl font-bold text-green-600">{{ $stats['active'] ?? 0 }}</p>
    </div>
    <div class="bg-gray-800 border border-gray-700 rounded-lg p-4 border-l-4 border-yellow-600">
        <p class="text-gray-400 text-xs">Monthly Salary Total</p>
        <p class="text-2xl font-bold text-yellow-600">₱{{ number_format($stats['total_monthly_salary'] ?? 0, 2) }}</p>
    </div>
</div>

<!-- Filter -->
<div class="bg-gray-800 border border-gray-700 rounded-lg p-4 mb-6">
    <form method="GET" class="flex flex-wrap gap-4">
        <select name="department" class="px-3 py-2 bg-gray-700 text-white border border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500">
            <option value="">All Departments</option>
            @foreach(['farm_operations', 'hr', 'finance', 'logistics', 'sales', 'admin'] as $dept)
            <option value="{{ $dept }}" {{ request('department') === $dept ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $dept)) }}</option>
            @endforeach
        </select>
        <select name="status" class="px-3 py-2 bg-gray-700 text-white border border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500">
            <option value="">All Status</option>
            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
            <option value="on_leave" {{ request('status') === 'on_leave' ? 'selected' : '' }}>On Leave</option>
            <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>Suspended</option>
            <option value="terminated" {{ request('status') === 'terminated' ? 'selected' : '' }}>Terminated</option>
            <option value="resigned" {{ request('status') === 'resigned' ? 'selected' : '' }}>Resigned</option>
        </select>
        <button type="submit" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-500">Filter</button>
    </form>
</div>

<!-- Table -->
<div class="bg-gray-800 border border-gray-700 rounded-lg">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-700">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Employee ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Department</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Position</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Hire Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Rate</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Rating</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-600">
                @forelse($employees as $emp)
                <tr class="hover:bg-gray-700">
                    <td class="px-6 py-4 font-mono text-sm">{{ $emp->employee_id }}</td>
                    <td class="px-6 py-4 font-medium text-white">{{ $emp->full_name }}</td>
                    <td class="px-6 py-4"><span class="px-2 py-1 text-xs bg-blue-900 text-blue-300 rounded-full">{{ ucfirst(str_replace('_', ' ', $emp->department)) }}</span></td>
                    <td class="px-6 py-4 text-gray-300">{{ $emp->position }}</td>
                    <td class="px-6 py-4 text-gray-300">{{ $emp->hire_date->format('M d, Y') }}</td>
                    <td class="px-6 py-4 text-gray-300">₱{{ number_format($emp->daily_rate ?? ($emp->monthly_salary / 26), 2) }}/day</td>
                    <td class="px-6 py-4 text-gray-300">{{ $emp->performance_rating ?? 3 }}/5</td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 text-xs rounded-full 
                            @if($emp->status === 'active') bg-green-900 text-green-300
                            @elseif($emp->status === 'on_leave') bg-yellow-900 text-yellow-300
                            @else bg-red-900 text-red-300 @endif">
                            {{ ucfirst(str_replace('_', ' ', $emp->status)) }}
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex gap-2">
                            <a href="{{ route('employees.show', $emp) }}" class="text-blue-400 hover:text-blue-300">View</a>
                            <a href="{{ route('employees.edit', $emp) }}" class="text-green-400 hover:text-green-300">Edit</a>
                            @if(Auth::user()?->isFarmOwner() || Auth::user()?->isHR())
                            <form method="POST" action="{{ route('employees.destroy', $emp) }}" onsubmit="return confirm('Delete this employee account? This will also delete their login access.');" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-400 hover:text-red-300">Delete</button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="px-6 py-8 text-center text-gray-400">No employees found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($employees->hasPages())
    <div class="p-6 border-t border-gray-600">{{ $employees->links() }}</div>
    @endif
</div>
@endsection
