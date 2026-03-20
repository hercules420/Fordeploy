@extends(auth()->user()?->isHR() ? 'hr.layouts.app' : 'farmowner.layouts.app')

@section('title', 'Edit Employee')
@section('header', 'Edit Employee')

@section('content')
<div class="max-w-2xl">
    <form action="{{ route('employees.update', $employee) }}" method="POST" class="bg-gray-800 border border-gray-700 rounded-lg p-6">
        @csrf
        @method('PUT')
        
        <!-- Basic Info -->
        <h3 class="font-semibold text-lg mb-4 pb-2 border-b border-gray-600">Basic Information</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">First Name *</label>
                <input type="text" name="first_name" value="{{ old('first_name', $employee->first_name) }}" required
                    class="w-full px-3 py-2 border border-gray-600 rounded-lg bg-white text-black placeholder-gray-500 focus:ring-2 focus:ring-green-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Last Name *</label>
                <input type="text" name="last_name" value="{{ old('last_name', $employee->last_name) }}" required
                    class="w-full px-3 py-2 border border-gray-600 rounded-lg bg-white text-black placeholder-gray-500 focus:ring-2 focus:ring-green-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Phone</label>
                <input type="tel" name="phone" value="{{ old('phone', $employee->phone) }}"
                    class="w-full px-3 py-2 border border-gray-600 rounded-lg bg-white text-black placeholder-gray-500 focus:ring-2 focus:ring-green-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Email</label>
                <input type="email" name="email" value="{{ old('email', $employee->email) }}"
                    class="w-full px-3 py-2 border border-gray-600 rounded-lg bg-white text-black placeholder-gray-500 focus:ring-2 focus:ring-green-500">
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-300 mb-1">Address</label>
                <textarea name="address" rows="2"
                    class="w-full px-3 py-2 border border-gray-600 rounded-lg bg-white text-black placeholder-gray-500 focus:ring-2 focus:ring-green-500">{{ old('address', $employee->address) }}</textarea>
            </div>
        </div>

        <!-- Employment Details -->
        <h3 class="font-semibold text-lg mb-4 pb-2 border-b border-gray-600">Employment Details</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            @if(Auth::user()?->isFarmOwner())
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Department *</label>
                <select name="department" required
                    class="w-full px-3 py-2 border border-gray-600 rounded-lg bg-white text-black focus:ring-2 focus:ring-green-500">
                    <option value="farm_operations" {{ old('department', $employee->department) === 'farm_operations' ? 'selected' : '' }}>Farm Operations</option>
                    <option value="hr" {{ old('department', $employee->department) === 'hr' ? 'selected' : '' }}>HR</option>
                    <option value="finance" {{ old('department', $employee->department) === 'finance' ? 'selected' : '' }}>Finance</option>
                    <option value="logistics" {{ old('department', $employee->department) === 'logistics' ? 'selected' : '' }}>Logistics</option>
                    <option value="sales" {{ old('department', $employee->department) === 'sales' ? 'selected' : '' }}>Sales</option>
                    <option value="admin" {{ old('department', $employee->department) === 'admin' ? 'selected' : '' }}>Admin</option>
                </select>
            </div>
            @else
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Department</label>
                <input type="text" value="{{ ucfirst(str_replace('_', ' ', $employee->department)) }}" disabled
                    class="w-full px-3 py-2 border border-gray-600 rounded-lg bg-gray-200 text-gray-700 cursor-not-allowed">
            </div>
            @endif
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Position *</label>
                <input type="text" name="position" value="{{ old('position', $employee->position) }}" required
                    class="w-full px-3 py-2 border border-gray-600 rounded-lg bg-white text-black placeholder-gray-500 focus:ring-2 focus:ring-green-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Employment Type</label>
                <select name="employment_type"
                    class="w-full px-3 py-2 border border-gray-600 rounded-lg bg-white text-black focus:ring-2 focus:ring-green-500">
                    <option value="full_time" {{ old('employment_type', $employee->employment_type) === 'full_time' ? 'selected' : '' }}>Full-time</option>
                    <option value="part_time" {{ old('employment_type', $employee->employment_type) === 'part_time' ? 'selected' : '' }}>Part-time</option>
                    <option value="contract" {{ old('employment_type', $employee->employment_type) === 'contract' ? 'selected' : '' }}>Contract</option>
                    <option value="seasonal" {{ old('employment_type', $employee->employment_type) === 'seasonal' ? 'selected' : '' }}>Seasonal</option>
                </select>
            </div>
            @if(Auth::user()?->isFarmOwner())
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Status</label>
                <select name="status"
                    class="w-full px-3 py-2 border border-gray-600 rounded-lg bg-white text-black focus:ring-2 focus:ring-green-500">
                    <option value="active" {{ old('status', $employee->status) === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="on_leave" {{ old('status', $employee->status) === 'on_leave' ? 'selected' : '' }}>On Leave</option>
                    <option value="suspended" {{ old('status', $employee->status) === 'suspended' ? 'selected' : '' }}>Suspended</option>
                    <option value="terminated" {{ old('status', $employee->status) === 'terminated' ? 'selected' : '' }}>Terminated</option>
                    <option value="resigned" {{ old('status', $employee->status) === 'resigned' ? 'selected' : '' }}>Resigned</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Daily Rate (₱) *</label>
                <input type="number" name="daily_rate" value="{{ old('daily_rate', $employee->daily_rate) }}" step="0.01" required
                    class="w-full px-3 py-2 border border-gray-600 rounded-lg bg-white text-black placeholder-gray-500 focus:ring-2 focus:ring-green-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Performance Rating (1-5)</label>
                <select name="performance_rating" class="w-full px-3 py-2 border border-gray-600 rounded-lg bg-white text-black focus:ring-2 focus:ring-green-500">
                    @for($i = 1; $i <= 5; $i++)
                        <option value="{{ $i }}" {{ (int) old('performance_rating', $employee->performance_rating ?? 3) === $i ? 'selected' : '' }}>{{ $i }}</option>
                    @endfor
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Date Hired</label>
                <input type="date" name="hire_date" value="{{ old('hire_date', $employee->hire_date?->format('Y-m-d')) }}"
                    class="w-full px-3 py-2 border border-gray-600 rounded-lg bg-white text-black focus:ring-2 focus:ring-green-500">
            </div>
            @else
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Status</label>
                <input type="text" value="{{ ucfirst(str_replace('_', ' ', $employee->status)) }}" disabled
                    class="w-full px-3 py-2 border border-gray-600 rounded-lg bg-gray-200 text-gray-700 cursor-not-allowed">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Daily Rate (₱)</label>
                <input type="text" value="₱{{ number_format($employee->daily_rate ?? 0, 2) }}" disabled
                    class="w-full px-3 py-2 border border-gray-600 rounded-lg bg-gray-200 text-gray-700 cursor-not-allowed">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Performance Rating</label>
                <input type="text" value="{{ $employee->performance_rating ?? 3 }}/5" disabled
                    class="w-full px-3 py-2 border border-gray-600 rounded-lg bg-gray-200 text-gray-700 cursor-not-allowed">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Hire Date</label>
                <input type="date" name="hire_date" value="{{ old('hire_date', $employee->hire_date?->format('Y-m-d')) }}"
                    class="w-full px-3 py-2 border border-gray-600 rounded-lg bg-white text-black focus:ring-2 focus:ring-green-500">
            </div>
            @endif
        </div>

        @unless(Auth::user()?->isFarmOwner())
        <div class="mb-6 rounded-lg border border-yellow-700 bg-yellow-900/30 px-4 py-3 text-sm text-yellow-200">
            HR can update general employee details, but only the farm owner can change department, salary, status, or delete employee accounts.
        </div>
        @endunless

        <div class="flex gap-3">
            <button type="submit" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">Update Employee</button>
            <a href="{{ route('employees.index') }}" class="px-6 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-500">Cancel</a>
        </div>
    </form>
</div>
@endsection
