<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\User;

class AttendanceAutomationService
{
    public function handleLogin(User $user): void
    {
        if (!$user->isDepartmentRole()) {
            return;
        }

        $employee = Employee::where('user_id', $user->id)->first();

        if (!$employee || !$employee->farm_owner_id) {
            return;
        }

        $attendance = Attendance::firstOrCreate(
            [
                'employee_id' => $employee->id,
                'work_date' => today(),
            ],
            [
                'farm_owner_id' => $employee->farm_owner_id,
                'status' => 'present',
            ]
        );

        if (!$attendance->time_in) {
            $attendance->clockIn();
        }
    }

    public function handleLogout(?User $user): void
    {
        if (!$user || !$user->isDepartmentRole()) {
            return;
        }

        $employee = Employee::where('user_id', $user->id)->first();

        if (!$employee) {
            return;
        }

        $attendance = Attendance::where('employee_id', $employee->id)
            ->whereDate('work_date', today())
            ->first();

        if (!$attendance || !$attendance->time_in || $attendance->time_out) {
            return;
        }

        $attendance->clockOut();
    }
}
