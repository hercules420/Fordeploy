<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\FarmOwner;
use App\Models\Payroll;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollWorkflowAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_finance_cannot_access_hr_payroll_preparation_routes(): void
    {
        [$farmOwner, $financeUser] = $this->createDepartmentContext('finance');
        $targetEmployee = $this->createEmployee($farmOwner, null, [
            'department' => 'farm_operations',
            'position' => 'Worker',
            'daily_rate' => 750,
        ]);

        $this->actingAs($financeUser);

        $this->get(route('payroll.create'))->assertForbidden();

        $this->post(route('payroll.store'), [
            'employee_id' => $targetEmployee->id,
            'period_start' => now()->subDays(7)->toDateString(),
            'period_end' => now()->toDateString(),
            'days_worked' => 6,
            'basic_pay' => 4500,
        ])->assertForbidden();

        $this->post(route('payroll.generateBatch'), [
            'period_start' => now()->subDays(7)->toDateString(),
            'period_end' => now()->toDateString(),
            'employee_ids' => [$targetEmployee->id],
        ])->assertForbidden();
    }

    public function test_hr_can_access_payroll_preparation_route(): void
    {
        [$farmOwner, $hrUser] = $this->createDepartmentContext('hr');
        $this->createEmployee($farmOwner, null, [
            'department' => 'farm_operations',
            'position' => 'Worker',
        ]);

        $this->actingAs($hrUser)
            ->get(route('payroll.create'))
            ->assertOk();
    }

    public function test_finance_can_approve_then_owner_approves_before_release(): void
    {
        [$farmOwner, $financeUser] = $this->createDepartmentContext('finance');
        $ownerUser = User::findOrFail((int) $farmOwner->user_id);

        $worker = $this->createEmployee($farmOwner, null, [
            'department' => 'farm_operations',
            'position' => 'Worker',
            'daily_rate' => 700,
        ]);

        $payroll = Payroll::create([
            'farm_owner_id' => $farmOwner->id,
            'employee_id' => $worker->id,
            'processed_by' => $financeUser->id,
            'payroll_period' => 'PAY-TEST-00001',
            'period_start' => now()->subDays(14)->toDateString(),
            'period_end' => now()->subDays(1)->toDateString(),
            'pay_date' => now()->toDateString(),
            'days_worked' => 10,
            'hours_worked' => 80,
            'regular_hours' => 80,
            'hourly_rate' => 87.50,
            'overtime_hours' => 0,
            'basic_pay' => 7000,
            'overtime_pay' => 0,
            'gross_pay' => 7000,
            'total_deductions' => 0,
            'net_pay' => 7000,
            'workflow_status' => 'pending_finance',
            'status' => 'pending',
        ]);

        $this->actingAs($financeUser)
            ->post(route('payroll.financeApprove', $payroll))
            ->assertRedirect();

        $payroll->refresh();
        $this->assertSame('finance_approved', $payroll->workflow_status);
        $this->assertSame('pending', $payroll->status);
        $this->assertSame($financeUser->id, $payroll->finance_approved_by);

        $this->actingAs($financeUser)
            ->post(route('payroll.releasePayslip', $payroll))
            ->assertRedirect();

        $payroll->refresh();
        $this->assertNotSame('released', $payroll->workflow_status);

        $this->actingAs($ownerUser)
            ->post(route('payroll.approve', $payroll))
            ->assertRedirect();

        $payroll->refresh();
        $this->assertSame('owner_approved', $payroll->workflow_status);
        $this->assertSame('approved', $payroll->status);
        $this->assertSame($ownerUser->id, $payroll->owner_approved_by);

        $this->actingAs($financeUser)
            ->post(route('payroll.releasePayslip', $payroll))
            ->assertRedirect();

        $payroll->refresh();
        $this->assertSame('released', $payroll->workflow_status);
        $this->assertSame($financeUser->id, $payroll->payslip_released_by);
    }

    public function test_owner_prepares_and_finance_executes_disbursement_with_reference(): void
    {
        $ownerUser = $this->createUserWithRole('farm_owner');
        $farmOwner = $this->createFarmOwnerFor($ownerUser, true);
        $this->createActiveSubscription($farmOwner);

        $financeUser = $this->createUserWithRole('finance');
        $this->createEmployee($farmOwner, $financeUser, [
            'department' => 'finance',
            'position' => 'Finance Staff',
            'daily_rate' => 900,
            'performance_rating' => 3,
        ]);

        $employee = $this->createEmployee($farmOwner, null, [
            'department' => 'farm_operations',
            'position' => 'Worker',
        ]);

        $payroll = Payroll::create([
            'farm_owner_id' => $farmOwner->id,
            'employee_id' => $employee->id,
            'processed_by' => $ownerUser->id,
            'payroll_period' => 'PAY-TEST-00002',
            'period_start' => now()->subDays(14)->toDateString(),
            'period_end' => now()->subDays(1)->toDateString(),
            'pay_date' => now()->toDateString(),
            'days_worked' => 10,
            'hours_worked' => 80,
            'regular_hours' => 80,
            'hourly_rate' => 87.50,
            'overtime_hours' => 0,
            'basic_pay' => 7000,
            'overtime_pay' => 0,
            'gross_pay' => 7000,
            'total_deductions' => 0,
            'net_pay' => 7000,
            'workflow_status' => 'released',
            'status' => 'approved',
        ]);

        $this->actingAs($ownerUser)
            ->post(route('payroll.prepareDisbursement', $payroll), ['payment_method' => 'bank_transfer'])
            ->assertRedirect();

        $payroll->refresh();
        $this->assertSame('ready_for_disbursement', $payroll->workflow_status);
        $this->assertSame('bank_transfer', $payroll->payment_method);
        $this->assertSame($ownerUser->id, $payroll->disbursement_prepared_by);

        $this->actingAs($financeUser)
            ->post(route('payroll.executeDisbursement', $payroll), ['disbursement_reference' => 'BTR-2026-0001'])
            ->assertRedirect();

        $payroll->refresh();
        $this->assertSame('paid', $payroll->status);
        $this->assertSame('paid', $payroll->workflow_status);
        $this->assertSame('bank_transfer', $payroll->payment_method);
        $this->assertSame('BTR-2026-0001', $payroll->disbursement_reference);
        $this->assertSame($financeUser->id, $payroll->disbursed_by);
    }

    public function test_overtime_multiplier_is_capped_at_two_x_in_batch_generation(): void
    {
        [$farmOwner, $hrUser] = $this->createDepartmentContext('hr');

        $worker = $this->createEmployee($farmOwner, null, [
            'department' => 'farm_operations',
            'position' => 'Worker',
            'daily_rate' => 800,
            'performance_rating' => 5,
        ]);

        Attendance::create([
            'farm_owner_id' => $farmOwner->id,
            'employee_id' => $worker->id,
            'work_date' => now()->toDateString(),
            'time_in' => '07:00:00',
            'time_out' => '23:30:00',
            'hours_worked' => 15.50,
            'overtime_hours' => 7.00,
            'late_minutes' => 0,
            'undertime_minutes' => 0,
            'status' => 'present',
        ]);

        $this->actingAs($hrUser)
            ->post(route('payroll.generateBatch'), [
                'period_start' => now()->subDay()->toDateString(),
                'period_end' => now()->toDateString(),
                'employee_ids' => [$worker->id],
            ])
            ->assertRedirect();

        $payroll = Payroll::where('farm_owner_id', $farmOwner->id)
            ->where('employee_id', $worker->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($payroll);

        $expectedOvertimePay = 7.00 * (800 / 8) * 2.00; // capped at 2.0x
        $this->assertEqualsWithDelta($expectedOvertimePay, (float) $payroll->overtime_pay, 0.01);
        $this->assertStringContainsString('cap:2.00', (string) $payroll->notes);
    }

    private function createDepartmentContext(string $role): array
    {
        $ownerUser = $this->createUserWithRole('farm_owner');
        $farmOwner = $this->createFarmOwnerFor($ownerUser, true);
        $this->createActiveSubscription($farmOwner);

        $departmentUser = $this->createUserWithRole($role);
        $this->createEmployee($farmOwner, $departmentUser, [
            'department' => $role,
            'position' => strtoupper($role) . ' Staff',
            'daily_rate' => 900,
            'performance_rating' => 3,
        ]);

        return [$farmOwner, $departmentUser];
    }

    private function createUserWithRole(string $role): User
    {
        return User::factory()->create([
            'role' => $role,
            'status' => 'active',
            'kyc_verified' => true,
            'email_verified_at' => now(),
        ]);
    }

    private function createFarmOwnerFor(User $ownerUser, bool $approved): FarmOwner
    {
        return FarmOwner::create([
            'user_id' => $ownerUser->id,
            'farm_name' => 'Demo Farm ' . $ownerUser->id,
            'farm_address' => 'Sitio Demo',
            'city' => 'Cebu City',
            'province' => 'Cebu',
            'postal_code' => '6000',
            'permit_status' => $approved ? 'approved' : 'pending',
            'subscription_status' => 'active',
        ]);
    }

    private function createActiveSubscription(FarmOwner $farmOwner): Subscription
    {
        return Subscription::create([
            'farm_owner_id' => $farmOwner->id,
            'plan_type' => 'professional',
            'monthly_cost' => 500,
            'product_limit' => 10,
            'order_limit' => 200,
            'commission_rate' => 3,
            'status' => 'active',
            'started_at' => now()->subDays(3),
            'ends_at' => now()->addMonth(),
        ]);
    }

    private function createEmployee(FarmOwner $farmOwner, ?User $linkedUser, array $overrides = []): Employee
    {
        return Employee::create(array_merge([
            'farm_owner_id' => $farmOwner->id,
            'user_id' => $linkedUser?->id,
            'employee_id' => 'EMP-' . strtoupper(substr(md5((string) microtime(true) . rand()), 0, 6)),
            'first_name' => 'Test',
            'last_name' => 'Employee',
            'department' => 'farm_operations',
            'position' => 'Farm Worker',
            'employment_type' => 'full_time',
            'hire_date' => now()->subYear()->toDateString(),
            'daily_rate' => 700,
            'monthly_salary' => 0,
            'performance_rating' => 3,
            'status' => 'active',
        ], $overrides));
    }
}
