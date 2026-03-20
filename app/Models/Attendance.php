<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class Attendance extends Model
{
    use HasFactory;

    protected $table = 'attendance';

    protected $fillable = [
        'farm_owner_id', 'employee_id', 'work_date', 'time_in', 'time_out',
        'break_start', 'break_end', 'hours_worked', 'overtime_hours', 'late_minutes',
        'undertime_minutes', 'status', 'leave_type', 'notes', 'approved_by'
    ];

    protected $casts = [
        'work_date' => 'date',
        'time_in' => 'datetime:H:i',
        'time_out' => 'datetime:H:i',
        'break_start' => 'datetime:H:i',
        'break_end' => 'datetime:H:i',
        'hours_worked' => 'decimal:2',
        'overtime_hours' => 'decimal:2',
        'late_minutes' => 'decimal:2',
        'undertime_minutes' => 'decimal:2',
    ];

    // Relationships
    public function farmOwner()
    {
        return $this->belongsTo(FarmOwner::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Query Scopes
    public function scopeByFarmOwner(Builder $query, int $farmOwnerId)
    {
        return $query->where('farm_owner_id', $farmOwnerId);
    }

    public function scopeByEmployee(Builder $query, int $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopeByDateRange(Builder $query, $startDate, $endDate)
    {
        return $query->whereBetween('work_date', [$startDate, $endDate]);
    }

    public function scopeToday(Builder $query)
    {
        return $query->whereDate('work_date', today());
    }

    public function scopePresent(Builder $query)
    {
        return $query->whereIn('status', ['present', 'late']);
    }

    public function scopeAbsent(Builder $query)
    {
        return $query->where('status', 'absent');
    }

    public function scopeOnLeave(Builder $query)
    {
        return $query->where('status', 'on_leave');
    }

    // Methods
    public function clockIn(): void
    {
        $this->update([
            'time_in' => now()->format('H:i:s'),
            'status' => 'present'
        ]);
    }

    public function clockOut(): void
    {
        $this->update([
            'time_out' => now()->format('H:i:s')
        ]);
        $this->calculateHoursWorked();
    }

    public function calculateHoursWorked(): void
    {
        if (!$this->time_in || !$this->time_out) return;

        $workDate = $this->work_date instanceof Carbon
            ? $this->work_date->copy()->startOfDay()
            : Carbon::parse((string) $this->work_date)->startOfDay();

        $timeIn = Carbon::parse($workDate->format('Y-m-d') . ' ' . Carbon::parse($this->time_in)->format('H:i:s'));
        $timeOut = Carbon::parse($workDate->format('Y-m-d') . ' ' . Carbon::parse($this->time_out)->format('H:i:s'));

        if ($timeOut->lt($timeIn)) {
            // Handle accidental overnight capture by rolling time_out to next day.
            $timeOut->addDay();
        }

        $breakMinutes = 0;

        if ($this->break_start && $this->break_end) {
            $breakStart = Carbon::parse($workDate->format('Y-m-d') . ' ' . Carbon::parse($this->break_start)->format('H:i:s'));
            $breakEnd = Carbon::parse($workDate->format('Y-m-d') . ' ' . Carbon::parse($this->break_end)->format('H:i:s'));

            // If the break starts after midnight for overnight shifts, align it to next day.
            if ($breakStart->lt($timeIn) && $timeOut->gt($timeIn->copy()->addHours(4))) {
                $breakStart->addDay();
                $breakEnd->addDay();
            }

            // Support break windows that cross midnight (e.g., 11:50 PM to 12:10 AM).
            if ($breakEnd->lt($breakStart)) {
                $breakEnd->addDay();
            }

            $breakMinutes = $breakStart->diffInMinutes($breakEnd);
        } else {
            // Auto-deduct a 1-hour lunch break for shifts that span 12:00 PM to 1:00 PM.
            $lunchStart = Carbon::parse($workDate->format('Y-m-d') . ' 12:00:00');
            $lunchEnd = Carbon::parse($workDate->format('Y-m-d') . ' 13:00:00');

            if ($timeIn->lt($lunchEnd) && $timeOut->gt($lunchStart)) {
                $breakMinutes = 60;
            }
        }

        $scheduledStart = Carbon::parse($workDate->format('Y-m-d') . ' 07:00:00');
        $scheduledEnd = Carbon::parse($workDate->format('Y-m-d') . ' 16:00:00');
        $overtimeStart = Carbon::parse($workDate->format('Y-m-d') . ' 16:30:00');

        $totalMinutes = $timeIn->diffInMinutes($timeOut) - $breakMinutes;

        $this->hours_worked = round(max(0, $totalMinutes) / 60, 2);
        $this->late_minutes = max(0, $scheduledStart->diffInMinutes($timeIn, false));

        $this->undertime_minutes = $timeOut->lt($scheduledEnd)
            ? $timeOut->diffInMinutes($scheduledEnd)
            : 0;

        // Overtime rule: starts only after 4:30 PM and only if total activity exceeds 8 hours.
        if ($timeOut->gt($overtimeStart) && $this->hours_worked > 8) {
            $overtimeMinutes = $overtimeStart->diffInMinutes($timeOut);
            $this->overtime_hours = round(max(0, $overtimeMinutes) / 60, 2);
        } else {
            $this->overtime_hours = 0;
        }

        if ($this->status === 'present' && $this->late_minutes > 0) {
            $this->status = 'late';
        }

        $this->save();
    }
}
