@extends(auth()->user()?->isHR() ? 'hr.layouts.app' : 'farmowner.layouts.app')

@section('title', 'Employee Details')
@section('header', 'Employee Details')
@section('subheader', $employee->full_name)

@section('header-actions')
<div class="flex gap-2">
    <a href="{{ route('employees.edit', $employee) }}" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">Edit Employee</a>
    @if(Auth::user()?->isFarmOwner() || Auth::user()?->isHR())
    <form method="POST" action="{{ route('employees.destroy', $employee) }}" onsubmit="return confirm('Delete this employee account? This will also delete their login access.');" class="inline">
        @csrf
        @method('DELETE')
        <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">Delete Employee</button>
    </form>
    @endif
    <a href="{{ route('employees.index') }}" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-500">Back</a>
</div>
@endsection

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 bg-gray-800 border border-gray-700 rounded-lg p-6">
        <h3 class="text-lg font-bold text-white mb-4">Profile</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div><p class="text-gray-400">Employee ID</p><p class="text-white font-semibold">{{ $employee->employee_id }}</p></div>
            <div><p class="text-gray-400">Department</p><p class="text-white font-semibold">{{ ucfirst(str_replace('_', ' ', $employee->department)) }}</p></div>
            <div><p class="text-gray-400">Position</p><p class="text-white font-semibold">{{ $employee->position }}</p></div>
            <div><p class="text-gray-400">Employment Type</p><p class="text-white font-semibold">{{ ucfirst(str_replace('_', ' ', $employee->employment_type)) }}</p></div>
            <div><p class="text-gray-400">Hire Date</p><p class="text-white font-semibold">{{ $employee->hire_date?->format('M d, Y') }}</p></div>
            <div><p class="text-gray-400">Status</p><p class="text-white font-semibold">{{ ucfirst(str_replace('_', ' ', $employee->status)) }}</p></div>
            <div><p class="text-gray-400">Email</p><p class="text-white font-semibold">{{ $employee->email ?? 'N/A' }}</p></div>
            <div><p class="text-gray-400">Phone</p><p class="text-white font-semibold">{{ $employee->phone ?? 'N/A' }}</p></div>
            <div><p class="text-gray-400">Daily Rate</p><p class="text-white font-semibold">₱{{ number_format($employee->daily_rate ?? 0, 2) }}</p></div>
            <div><p class="text-gray-400">Monthly Salary</p><p class="text-white font-semibold">₱{{ number_format($employee->monthly_salary ?? 0, 2) }}</p></div>
            <div><p class="text-gray-400">Performance Rating</p><p class="text-white font-semibold">{{ $employee->performance_rating ?? 3 }}/5</p></div>
        </div>
    </div>

    <div class="bg-gray-800 border border-gray-700 rounded-lg p-6">
        <h3 class="text-lg font-bold text-white mb-4">Attendance (Latest)</h3>
        @if($employee->attendance->count())
            <div class="space-y-3 text-sm">
                @foreach($employee->attendance->take(5) as $row)
                    <div class="border border-gray-700 rounded p-3">
                        <p class="text-white font-semibold">{{ $row->work_date?->format('M d, Y') }}</p>
                        <p class="text-gray-400">Status: {{ ucfirst($row->status ?? 'N/A') }}</p>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-gray-400 text-sm">No attendance records.</p>
        @endif
    </div>
</div>

<div class="mt-6 bg-gray-800 border border-gray-700 rounded-lg p-6">
    <h3 class="text-lg font-bold text-white mb-4">Payroll (Latest)</h3>
    @if($employee->payroll->count())
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="border-b border-gray-700 text-gray-400">
                    <tr>
                        <th class="text-left py-2">Period</th>
                        <th class="text-left py-2">Gross Pay</th>
                        <th class="text-left py-2">Net Pay</th>
                        <th class="text-left py-2">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700">
                    @foreach($employee->payroll->take(8) as $pay)
                    <tr>
                        <td class="py-2 text-white">{{ $pay->period_start?->format('M d, Y') }} - {{ $pay->period_end?->format('M d, Y') }}</td>
                        <td class="py-2 text-white">₱{{ number_format($pay->gross_pay ?? 0, 2) }}</td>
                        <td class="py-2 text-white">₱{{ number_format($pay->net_pay ?? 0, 2) }}</td>
                        <td class="py-2 text-gray-300">{{ ucfirst($pay->status ?? 'N/A') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <p class="text-gray-400 text-sm">No payroll records.</p>
    @endif
</div>
@endsection
