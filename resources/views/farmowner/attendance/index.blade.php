@extends(auth()->user()?->isHR() ? 'hr.layouts.app' : 'farmowner.layouts.app')

@section('title', 'Attendance')
@section('header', 'Daily Attendance')
@section('subheader', 'Track employee time and attendance for ' . $date->format('F d, Y'))

@section('header-actions')
<a href="{{ route('attendance.report') }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">📊 Report</a>
@endsection

@section('content')
<!-- Date Selector -->
<div class="bg-gray-800 border border-gray-700 rounded-lg p-4 mb-6">
    <form method="GET" class="flex items-center gap-4">
        <label class="text-sm font-medium text-gray-300">Date:</label>
        <input type="date" name="date" value="{{ $date->format('Y-m-d') }}"
            class="px-3 py-2 bg-gray-700 text-white border border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500"
            onchange="this.form.submit()">
        <a href="{{ route('attendance.index', ['date' => now()->format('Y-m-d')]) }}" class="text-green-400 hover:text-green-300">Today</a>
    </form>
</div>

<!-- Stats -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-gray-800 border border-gray-700 rounded-lg p-4 border-l-4 border-green-600">
        <p class="text-gray-400 text-xs">Present</p>
        <p class="text-2xl font-bold text-green-600">{{ $stats['present'] }}</p>
    </div>
    <div class="bg-gray-800 border border-gray-700 rounded-lg p-4 border-l-4 border-red-600">
        <p class="text-gray-400 text-xs">Absent</p>
        <p class="text-2xl font-bold text-red-600">{{ $stats['absent'] }}</p>
    </div>
    <div class="bg-gray-800 border border-gray-700 rounded-lg p-4 border-l-4 border-yellow-600">
        <p class="text-gray-400 text-xs">On Leave</p>
        <p class="text-2xl font-bold text-yellow-600">{{ $stats['on_leave'] }}</p>
    </div>
    <div class="bg-gray-800 border border-gray-700 rounded-lg p-4 border-l-4 border-blue-600">
        <p class="text-gray-400 text-xs">Total Employees</p>
        <p class="text-2xl font-bold text-blue-600">{{ $stats['total_employees'] }}</p>
    </div>
</div>

<!-- Attendance Form -->
<div class="bg-gray-800 border border-gray-700 rounded-lg">
    <div class="p-6 border-b border-gray-600">
        <h3 class="font-semibold text-lg">Record Attendance</h3>
    </div>
    <form action="{{ route('attendance.bulk') }}" method="POST">
        @csrf
        <input type="hidden" name="work_date" value="{{ $date->format('Y-m-d') }}">
        
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Employee</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Position</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Time In</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Time Out</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-600">
                    @foreach($employees as $index => $emp)
                    @php
                        $record = $attendance->firstWhere('employee_id', $emp->id);
                    @endphp
                    <tr class="hover:bg-gray-700">
                        <td class="px-6 py-4 font-medium text-white">
                            <input type="hidden" name="attendance[{{ $index }}][employee_id]" value="{{ $emp->id }}">
                            {{ $emp->full_name }}
                        </td>
                        <td class="px-6 py-4 text-gray-300">{{ $emp->position }}</td>
                        <td class="px-6 py-4">
                            <select name="attendance[{{ $index }}][status]" required
                                class="px-2 py-1 border border-gray-600 rounded text-sm focus:ring-2 focus:ring-green-500">
                                <option value="present" {{ ($record?->status ?? '') === 'present' ? 'selected' : '' }}>Present</option>
                                <option value="absent" {{ ($record?->status ?? '') === 'absent' ? 'selected' : '' }}>Absent</option>
                                <option value="late" {{ ($record?->status ?? '') === 'late' ? 'selected' : '' }}>Late</option>
                                <option value="half_day" {{ ($record?->status ?? '') === 'half_day' ? 'selected' : '' }}>Half Day</option>
                                <option value="on_leave" {{ ($record?->status ?? '') === 'on_leave' ? 'selected' : '' }}>On Leave</option>
                                <option value="rest_day" {{ ($record?->status ?? '') === 'rest_day' ? 'selected' : '' }}>Rest Day</option>
                            </select>
                        </td>
                        <td class="px-6 py-4">
                            <input type="time" name="attendance[{{ $index }}][time_in]" 
                                value="{{ $record?->time_in?->format('H:i') ?? '' }}"
                                class="px-2 py-1 border border-gray-600 rounded text-sm focus:ring-2 focus:ring-green-500">
                        </td>
                        <td class="px-6 py-4">
                            <input type="time" name="attendance[{{ $index }}][time_out]" 
                                value="{{ $record?->time_out?->format('H:i') ?? '' }}"
                                class="px-2 py-1 border border-gray-600 rounded text-sm focus:ring-2 focus:ring-green-500">
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        
        <div class="p-6 border-t border-gray-600">
            <button type="submit" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">Save Attendance</button>
        </div>
    </form>
</div>
@endsection
