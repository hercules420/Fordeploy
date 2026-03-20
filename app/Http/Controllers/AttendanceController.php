<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\FarmOwner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    use \App\Http\Controllers\Concerns\ResolvesFarmOwner;

    public function index(Request $request)
    {
        $farmOwner = $this->getFarmOwner();
        $date = $request->filled('date') ? Carbon::parse($request->date) : today();
        
        $attendance = Attendance::byFarmOwner($farmOwner->id)
            ->with('employee:id,first_name,last_name,position')
            ->whereDate('work_date', $date)
            ->get();

        $employees = Employee::byFarmOwner($farmOwner->id)
            ->active()
            ->select('id', 'first_name', 'last_name', 'position')
            ->orderBy('last_name')
            ->get();

        $stats = [
            'present' => $attendance->whereIn('status', ['present', 'late'])->count(),
            'absent' => $attendance->where('status', 'absent')->count(),
            'on_leave' => $attendance->where('status', 'on_leave')->count(),
            'total_employees' => $employees->count(),
        ];

        return view('farmowner.attendance.index', compact('attendance', 'employees', 'stats', 'date'));
    }

    public function store(Request $request)
    {
        $farmOwner = $this->getFarmOwner();

        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'work_date' => 'required|date',
            'time_in' => 'nullable|date_format:H:i',
            'time_out' => 'nullable|date_format:H:i|after:time_in',
            'status' => 'required|in:present,absent,late,half_day,on_leave,holiday,rest_day',
            'leave_type' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
        ]);

        // Verify employee belongs to this farm owner
        $employee = \App\Models\Employee::where('id', $validated['employee_id'])
            ->where('farm_owner_id', $farmOwner->id)
            ->firstOrFail();

        $validated['farm_owner_id'] = $farmOwner->id;

        $attendance = Attendance::updateOrCreate(
            ['employee_id' => $validated['employee_id'], 'work_date' => $validated['work_date']],
            $validated
        );

        if ($attendance->time_in && $attendance->time_out) {
            $attendance->calculateHoursWorked();
        }

        return redirect()->route('attendance.index', ['date' => $validated['work_date']])
            ->with('success', 'Attendance recorded.');
    }

    public function clockIn(Request $request)
    {
        $farmOwner = $this->getFarmOwner();

        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
        ]);

        // Verify employee belongs to this farm owner
        $employee = \App\Models\Employee::where('id', $validated['employee_id'])
            ->where('farm_owner_id', $farmOwner->id)
            ->firstOrFail();

        $attendance = Attendance::firstOrCreate(
            ['employee_id' => $validated['employee_id'], 'work_date' => today()],
            ['farm_owner_id' => $farmOwner->id, 'status' => 'present']
        );

        $attendance->clockIn();

        return back()->with('success', 'Clock in recorded.');
    }

    public function clockOut(Request $request)
    {
        $farmOwner = $this->getFarmOwner();

        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
        ]);

        // Verify employee belongs to this farm owner
        $employee = \App\Models\Employee::where('id', $validated['employee_id'])
            ->where('farm_owner_id', $farmOwner->id)
            ->firstOrFail();

        $attendance = Attendance::where('employee_id', $validated['employee_id'])
            ->whereDate('work_date', today())
            ->first();

        if ($attendance) {
            $attendance->clockOut();
            return back()->with('success', 'Clock out recorded.');
        }

        return back()->with('error', 'No clock in record found for today.');
    }

    public function bulkStore(Request $request)
    {
        $farmOwner = $this->getFarmOwner();

        $validated = $request->validate([
            'work_date' => 'required|date',
            'attendance' => 'required|array',
            'attendance.*.employee_id' => 'required|exists:employees,id',
            'attendance.*.status' => 'required|in:present,absent,late,half_day,on_leave,holiday,rest_day',
            'attendance.*.time_in' => 'nullable|date_format:H:i',
            'attendance.*.time_out' => 'nullable|date_format:H:i',
        ]);

        // Verify all employee IDs belong to this farm owner
        $employeeIds = collect($validated['attendance'])->pluck('employee_id')->unique();
        $validCount = \App\Models\Employee::where('farm_owner_id', $farmOwner->id)
            ->whereIn('id', $employeeIds)
            ->count();
        
        if ($validCount !== $employeeIds->count()) {
            abort(403, 'One or more employees do not belong to your farm.');
        }

        foreach ($validated['attendance'] as $record) {
            Attendance::updateOrCreate(
                ['employee_id' => $record['employee_id'], 'work_date' => $validated['work_date']],
                array_merge($record, ['farm_owner_id' => $farmOwner->id])
            );
        }

        return redirect()->route('attendance.index', ['date' => $validated['work_date']])
            ->with('success', 'Bulk attendance saved.');
    }

    public function report(Request $request)
    {
        $farmOwner = $this->getFarmOwner();
        
        $startDate = $request->filled('start_date') ? Carbon::parse($request->start_date) : now()->startOfMonth();
        $endDate = $request->filled('end_date') ? Carbon::parse($request->end_date) : now()->endOfMonth();

        $employees = Employee::byFarmOwner($farmOwner->id)
            ->active()
            ->with(['attendance' => fn($q) => $q->byDateRange($startDate, $endDate)])
            ->get()
            ->map(function ($employee) {
                $attendance = $employee->attendance;
                return [
                    'employee' => $employee,
                    'present_days' => $attendance->whereIn('status', ['present', 'late'])->count(),
                    'absent_days' => $attendance->where('status', 'absent')->count(),
                    'leave_days' => $attendance->where('status', 'on_leave')->count(),
                    'total_hours' => $attendance->sum('hours_worked'),
                    'overtime_hours' => $attendance->sum('overtime_hours'),
                ];
            });

        return view('farmowner.attendance.report', compact('employees', 'startDate', 'endDate'));
    }
}
