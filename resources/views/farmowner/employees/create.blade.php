@extends(auth()->user()?->isHR() ? 'hr.layouts.app' : 'farmowner.layouts.app')

@section('title', 'Add Employee')
@section('header', 'Add New Employee')
@section('subheader', 'Register a new staff member')

@section('content')
<div class="max-w-3xl">
    <form action="{{ route('employees.store') }}" method="POST" class="bg-gray-800 border border-gray-700 rounded-lg p-6 space-y-6">
        @csrf
        
        <!-- Basic Info -->
        <div>
            <h4 class="font-medium text-white mb-4">Basic Information</h4>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Employee ID</label>
                    <input type="text" value="Auto-generated on save" disabled
                        class="w-full px-3 py-2 border border-gray-600 rounded-lg bg-gray-200 text-gray-700 cursor-not-allowed">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">First Name *</label>
                    <input type="text" name="first_name" value="{{ old('first_name') }}" required
                        class="w-full px-3 py-2 border border-gray-600 rounded-lg bg-white text-black placeholder-gray-500 focus:ring-2 focus:ring-green-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Last Name *</label>
                    <input type="text" name="last_name" value="{{ old('last_name') }}" required
                        class="w-full px-3 py-2 border border-gray-600 rounded-lg bg-white text-black placeholder-gray-500 focus:ring-2 focus:ring-green-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Email *</label>
                    <input type="email" name="email" value="{{ old('email') }}" required
                        class="w-full px-3 py-2 border border-gray-600 rounded-lg bg-white text-black placeholder-gray-500 focus:ring-2 focus:ring-green-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Phone</label>
                    <input type="text" name="phone" value="{{ old('phone') }}"
                        class="w-full px-3 py-2 border border-gray-600 rounded-lg bg-white text-black placeholder-gray-500 focus:ring-2 focus:ring-green-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Gender</label>
                    <select name="gender" class="w-full px-3 py-2 border border-gray-600 rounded-lg bg-white text-black focus:ring-2 focus:ring-green-500">
                        <option value="">Select</option>
                        <option value="male" {{ old('gender') === 'male' ? 'selected' : '' }}>Male</option>
                        <option value="female" {{ old('gender') === 'female' ? 'selected' : '' }}>Female</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Employment Info -->
        <div>
            <h4 class="font-medium text-white mb-4">Employment Details</h4>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Department *</label>
                    <select name="department" required class="w-full px-3 py-2 border border-gray-600 rounded-lg bg-white text-black focus:ring-2 focus:ring-green-500">
                        <option value="">Select</option>
                        @foreach(['farm_operations', 'hr', 'finance', 'logistics', 'sales', 'admin'] as $dept)
                        <option value="{{ $dept }}" {{ old('department') === $dept ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $dept)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Position *</label>
                    <input type="text" name="position" value="{{ old('position') }}" required
                        class="w-full px-3 py-2 border border-gray-600 rounded-lg bg-white text-black placeholder-gray-500 focus:ring-2 focus:ring-green-500"
                        placeholder="e.g., Farm Worker">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Employment Type *</label>
                    <select name="employment_type" required class="w-full px-3 py-2 border border-gray-600 rounded-lg bg-white text-black focus:ring-2 focus:ring-green-500">
                        <option value="full_time" {{ old('employment_type') === 'full_time' ? 'selected' : '' }}>Full Time</option>
                        <option value="part_time" {{ old('employment_type') === 'part_time' ? 'selected' : '' }}>Part Time</option>
                        <option value="contract" {{ old('employment_type') === 'contract' ? 'selected' : '' }}>Contract</option>
                        <option value="seasonal" {{ old('employment_type') === 'seasonal' ? 'selected' : '' }}>Seasonal</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Hire Date *</label>
                    <input type="date" name="hire_date" value="{{ old('hire_date', date('Y-m-d')) }}" required
                        class="w-full px-3 py-2 border border-gray-600 rounded-lg bg-white text-black focus:ring-2 focus:ring-green-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Daily Rate (₱)</label>
                    <input type="number" name="daily_rate" value="{{ old('daily_rate') }}" step="0.01" min="0"
                        class="w-full px-3 py-2 border border-gray-600 rounded-lg bg-white text-black placeholder-gray-500 focus:ring-2 focus:ring-green-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Monthly Salary (₱)</label>
                    <input type="number" name="monthly_salary" value="{{ old('monthly_salary') }}" step="0.01" min="0"
                        class="w-full px-3 py-2 border border-gray-600 rounded-lg bg-white text-black placeholder-gray-500 focus:ring-2 focus:ring-green-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Performance Rating (1-5)</label>
                    <select name="performance_rating" class="w-full px-3 py-2 border border-gray-600 rounded-lg bg-white text-black focus:ring-2 focus:ring-green-500">
                        @for($i = 1; $i <= 5; $i++)
                            <option value="{{ $i }}" {{ (int) old('performance_rating', 3) === $i ? 'selected' : '' }}>{{ $i }}</option>
                        @endfor
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Account Password *</label>
                    <input type="password" name="password" required
                        class="w-full px-3 py-2 border border-gray-600 rounded-lg bg-white text-black placeholder-gray-500 focus:ring-2 focus:ring-green-500"
                        placeholder="Minimum 8 characters">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Confirm Password *</label>
                    <input type="password" name="password_confirmation" required
                        class="w-full px-3 py-2 border border-gray-600 rounded-lg bg-white text-black placeholder-gray-500 focus:ring-2 focus:ring-green-500"
                        placeholder="Re-enter password">
                </div>
            </div>
        </div>

        <div class="flex gap-4 pt-4">
            <button type="submit" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">Add Employee</button>
            <a href="{{ route('employees.index') }}" class="px-6 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-500">Cancel</a>
        </div>
    </form>
</div>
@endsection
