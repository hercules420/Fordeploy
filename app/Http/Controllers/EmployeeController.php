<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\FarmOwner;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\URL;

class EmployeeController extends Controller
{
    use \App\Http\Controllers\Concerns\ResolvesFarmOwner;

    private function isFarmOwnerUser(): bool
    {
        return (bool) Auth::user()?->isFarmOwner();
    }

    private function statsCacheKey(int $farmOwnerId): string
    {
        return "farm_{$farmOwnerId}_employee_stats";
    }

    public function index(Request $request)
    {
        $farmOwner = $this->getFarmOwner();
        
        $query = Employee::byFarmOwner($farmOwner->id)
            ->select('id', 'employee_id', 'first_name', 'last_name', 'department', 'position', 'hire_date', 'daily_rate', 'performance_rating', 'status');

        if ($request->filled('department')) {
            $query->byDepartment($request->department);
        }

        if ($request->filled('status')) {
            $query->byStatus($request->status);
        }

        $employees = $query->orderBy('last_name')->paginate(20);

        $stats = Cache::remember($this->statsCacheKey($farmOwner->id), 300, function () use ($farmOwner) {
            $aggregate = Employee::byFarmOwner($farmOwner->id)
                ->selectRaw("COUNT(*) as total")
                ->selectRaw("SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active")
                ->selectRaw("COALESCE(SUM(CASE WHEN status = 'active' THEN monthly_salary ELSE 0 END), 0) as total_monthly_salary")
                ->first();

            return [
                'total' => (int) ($aggregate->total ?? 0),
                'active' => (int) ($aggregate->active ?? 0),
                'total_monthly_salary' => (float) ($aggregate->total_monthly_salary ?? 0),
            ];
        });

        return view('farmowner.employees.index', compact('employees', 'stats'));
    }

    public function create()
    {
        return view('farmowner.employees.create');
    }

    public function store(Request $request)
    {
        $farmOwner = $this->getFarmOwner();
        $verificationUrl = null;

        $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'middle_name' => 'nullable|string|max:100',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'birth_date' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:20',
            'department' => 'required|in:farm_operations,hr,finance,logistics,sales,admin',
            'position' => 'required|string|max:100',
            'employment_type' => 'required|in:full_time,part_time,contract,seasonal',
            'hire_date' => 'required|date',
            'daily_rate' => 'nullable|numeric|min:0',
            'monthly_salary' => 'nullable|numeric|min:0',
            'performance_rating' => 'nullable|integer|min:1|max:5',
            'sss_number' => 'nullable|string|max:20',
            'philhealth_number' => 'nullable|string|max:20',
            'pagibig_number' => 'nullable|string|max:20',
            'tin_number' => 'nullable|string|max:20',
            'bank_name' => 'nullable|string|max:100',
            'bank_account_number' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
        ]);

        DB::transaction(function () use ($validated, $farmOwner, &$verificationUrl) {
            $employeeUser = User::create([
                'name' => trim($validated['first_name'] . ' ' . $validated['last_name']),
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => $validated['department'],
                'status' => 'active',
                'email_verified_at' => null,
            ]);

            $employeeUser->sendEmailVerificationNotification();

            if (config('mail.default') === 'log') {
                $verificationUrl = URL::temporarySignedRoute(
                    'verification.verify',
                    now()->addMinutes(60),
                    [
                        'id' => $employeeUser->id,
                        'hash' => sha1($employeeUser->getEmailForVerification()),
                    ]
                );
            }

            $employeeData = collect($validated)
                ->except(['password', 'password_confirmation'])
                ->toArray();

            $employeeData['farm_owner_id'] = $farmOwner->id;
            $employeeData['user_id'] = $employeeUser->id;
            $employeeData['employee_id'] = $this->generateEmployeeId($farmOwner->id);

            Employee::create($employeeData);
        });

        Cache::forget($this->statsCacheKey($farmOwner->id));

        $message = 'Employee added successfully. Verification email sent to their account email.';

        if ($verificationUrl) {
            $message .= ' DEV verification link: ' . $verificationUrl;
        }

        return redirect()->route('employees.index')->with('success', $message);
    }

    private function generateEmployeeId(int $farmOwnerId): string
    {
        do {
            $employeeId = 'EMP-' . Str::upper(Str::random(6));
        } while (Employee::where('farm_owner_id', $farmOwnerId)->where('employee_id', $employeeId)->exists());

        return $employeeId;
    }

    public function show(Employee $employee)
    {
        $farmOwner = $this->getFarmOwner();
        abort_if($employee->farm_owner_id !== $farmOwner->id, 403);

        $employee->load([
            'attendance' => fn($q) => $q->latest('work_date')->limit(30),
            'payroll' => fn($q) => $q->latest('period_start')->limit(12),
        ]);

        return view('farmowner.employees.show', compact('employee'));
    }

    public function edit(Employee $employee)
    {
        $farmOwner = $this->getFarmOwner();
        abort_if($employee->farm_owner_id !== $farmOwner->id, 403);

        return view('farmowner.employees.edit', compact('employee'));
    }

    public function update(Request $request, Employee $employee)
    {
        $farmOwner = $this->getFarmOwner();
        abort_if($employee->farm_owner_id !== $farmOwner->id, 403);

        $rules = [
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'middle_name' => 'nullable|string|max:100',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:20',
            'position' => 'required|string|max:100',
            'employment_type' => 'nullable|in:full_time,part_time,contract,seasonal',
            'hire_date' => 'nullable|date',
            'bank_name' => 'nullable|string|max:100',
            'bank_account_number' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
        ];

        if ($this->isFarmOwnerUser()) {
            $rules['department'] = 'required|in:farm_operations,hr,finance,logistics,sales,admin';
            $rules['daily_rate'] = 'nullable|numeric|min:0';
            $rules['monthly_salary'] = 'nullable|numeric|min:0';
            $rules['performance_rating'] = 'nullable|integer|min:1|max:5';
            $rules['status'] = 'required|in:active,on_leave,suspended,terminated,resigned';
        }

        $validated = $request->validate($rules);

        $employee->update($validated);
        Cache::forget($this->statsCacheKey($farmOwner->id));

        return redirect()->route('employees.show', $employee)->with('success', 'Employee updated.');
    }

    public function destroy(Employee $employee)
    {
        $farmOwner = $this->getFarmOwner();
        abort_if($employee->farm_owner_id !== $farmOwner->id, 403);
        abort_unless(
            $this->isFarmOwnerUser() || Auth::user()?->isHR(),
            403,
            'Only farm owner or HR can delete employees.'
        );

        DB::transaction(function () use ($employee) {
            $linkedUser = $employee->user;

            $employee->delete();

            if ($linkedUser) {
                $linkedUser->delete();
            }
        });

        Cache::forget($this->statsCacheKey($farmOwner->id));

        return redirect()->route('employees.index')->with('success', 'Employee account removed.');
    }
}
