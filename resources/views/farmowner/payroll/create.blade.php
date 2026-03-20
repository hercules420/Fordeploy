@extends(auth()->user()?->isHR() ? 'hr.layouts.app' : 'farmowner.layouts.app')

@section('title', 'Create Payroll')
@section('header', 'Create Payroll Record')
@section('subheader', auth()->user()?->isHR() ? 'Prepare payroll for owner review and payment approval.' : 'Create a payroll record for this pay period.')

@section('content')
<div class="max-w-4xl">
    <div class="mb-4 rounded-lg border border-blue-700 bg-blue-900/30 px-4 py-3 text-sm text-blue-200">
        Payroll computation now uses attendance data for worked hours, late deductions, and overtime. Overtime starts only after 4:30 PM.
        HR submissions are routed to finance approval before payslip release.
    </div>

    <form action="{{ route('payroll.store') }}" method="POST" class="bg-gray-800 border border-gray-700 rounded-lg p-6">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Employee *</label>
                <select name="employee_id" required class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:ring-2 focus:ring-orange-500 text-white">
                    <option value="">Select employee</option>
                    @foreach($employees as $employee)
                        <option value="{{ $employee->id }}" {{ old('employee_id') == $employee->id ? 'selected' : '' }}>
                            {{ $employee->first_name }} {{ $employee->last_name }} - {{ $employee->position }}
                        </option>
                    @endforeach
                </select>
                @error('employee_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div></div>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Period Start *</label>
                <input type="date" name="period_start" value="{{ old('period_start') }}" required class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:ring-2 focus:ring-orange-500 text-white">
                @error('period_start')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Period End *</label>
                <input type="date" name="period_end" value="{{ old('period_end') }}" required class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:ring-2 focus:ring-orange-500 text-white">
                @error('period_end')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Days Worked *</label>
                <input type="number" step="0.5" min="0" name="days_worked" value="{{ old('days_worked', 0) }}" required class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:ring-2 focus:ring-orange-500 text-white">
                @error('days_worked')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Basic Pay *</label>
                <input type="number" step="0.01" min="0" name="basic_pay" value="{{ old('basic_pay', 0) }}" required class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:ring-2 focus:ring-orange-500 text-white">
                @error('basic_pay')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Overtime Hours</label>
                <input type="number" step="0.01" min="0" name="overtime_hours" value="{{ old('overtime_hours', 0) }}" class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:ring-2 focus:ring-orange-500 text-white">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Overtime Pay</label>
                <input type="number" step="0.01" min="0" name="overtime_pay" value="{{ old('overtime_pay', 0) }}" class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:ring-2 focus:ring-orange-500 text-white">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Holiday Pay</label>
                <input type="number" step="0.01" min="0" name="holiday_pay" value="{{ old('holiday_pay', 0) }}" class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:ring-2 focus:ring-orange-500 text-white">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Allowances</label>
                <input type="number" step="0.01" min="0" name="allowances" value="{{ old('allowances', 0) }}" class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:ring-2 focus:ring-orange-500 text-white">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Bonuses</label>
                <input type="number" step="0.01" min="0" name="bonuses" value="{{ old('bonuses', 0) }}" class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:ring-2 focus:ring-orange-500 text-white">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">SSS Deduction</label>
                <input type="number" step="0.01" min="0" name="sss_deduction" value="{{ old('sss_deduction', 0) }}" class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:ring-2 focus:ring-orange-500 text-white">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">PhilHealth Deduction</label>
                <input type="number" step="0.01" min="0" name="philhealth_deduction" value="{{ old('philhealth_deduction', 0) }}" class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:ring-2 focus:ring-orange-500 text-white">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Pag-IBIG Deduction</label>
                <input type="number" step="0.01" min="0" name="pagibig_deduction" value="{{ old('pagibig_deduction', 0) }}" class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:ring-2 focus:ring-orange-500 text-white">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Tax Deduction</label>
                <input type="number" step="0.01" min="0" name="tax_deduction" value="{{ old('tax_deduction', 0) }}" class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:ring-2 focus:ring-orange-500 text-white">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Loan Deduction</label>
                <input type="number" step="0.01" min="0" name="loan_deduction" value="{{ old('loan_deduction', 0) }}" class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:ring-2 focus:ring-orange-500 text-white">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Insurance Deduction</label>
                <input type="number" step="0.01" min="0" name="insurance_deduction" value="{{ old('insurance_deduction', 0) }}" class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:ring-2 focus:ring-orange-500 text-white">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Reimbursement Deduction</label>
                <input type="number" step="0.01" min="0" name="reimbursement_deduction" value="{{ old('reimbursement_deduction', 0) }}" class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:ring-2 focus:ring-orange-500 text-white">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Other Deductions</label>
                <input type="number" step="0.01" min="0" name="other_deductions" value="{{ old('other_deductions', 0) }}" class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:ring-2 focus:ring-orange-500 text-white">
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-300 mb-1">Notes</label>
                <textarea name="notes" rows="3" class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:ring-2 focus:ring-orange-500 text-white">{{ old('notes') }}</textarea>
            </div>
        </div>

        <div class="flex gap-3">
            <button type="submit" class="px-6 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700">Create Payroll</button>
            <a href="{{ route('payroll.index') }}" class="px-6 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-500">Cancel</a>
        </div>
    </form>
</div>
@endsection
