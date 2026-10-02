<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeCompensation;
use App\Models\Payroll;
use App\Models\PayrollItem;
use App\Models\PayrollPeriod;
use App\Models\PayrollPolicyVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PayrollPolicyConfigurationPayslipTest extends TestCase
{
    use RefreshDatabase;

    public function test_finance_can_append_policy_versions_and_period_uses_latest_effective_version(): void
    {
        $finance = $this->user(UserRole::Finance, 'finance@example.test');

        $this->actingAs($finance)->post('/finance/payroll-policies', [
            'policy_key' => 'absent_deduction',
            'enabled' => '1',
            'mode' => 'fixed_per_day',
            'value' => '100000.00',
            'effective_from' => '2026-09-01',
            'notes' => 'Policy September',
        ])->assertRedirect();

        $this->actingAs($finance)->post('/finance/payroll-policies', [
            'policy_key' => 'absent_deduction',
            'enabled' => '1',
            'mode' => 'fixed_per_day',
            'value' => '200000.00',
            'effective_from' => '2026-11-01',
            'notes' => 'Policy November',
        ])->assertRedirect();

        $period = PayrollPeriod::create([
            'name' => 'Oktober 2026',
            'period_start' => '2026-10-01',
            'period_end' => '2026-10-31',
            'status' => 'DRAFT',
            'created_by' => $finance->id,
        ]);

        $this->actingAs($finance)
            ->get('/finance/payroll/'.$period->id)
            ->assertOk()
            ->assertSee('100000.00')
            ->assertSee('efektif 01-09-2026')
            ->assertDontSee('200000.00');

        $this->assertDatabaseCount('payroll_policy_versions', 2);
    }

    public function test_duplicate_policy_key_and_effective_date_is_rejected(): void
    {
        $finance = $this->user(UserRole::Finance, 'finance@example.test');

        PayrollPolicyVersion::create([
            'policy_key' => 'late_deduction',
            'enabled' => true,
            'mode' => 'fixed_per_minute',
            'value' => '1000.00',
            'effective_from' => '2026-10-01',
            'created_by' => $finance->id,
        ]);

        $this->actingAs($finance)->post('/finance/payroll-policies', [
            'policy_key' => 'late_deduction',
            'enabled' => '1',
            'mode' => 'fixed_per_incident',
            'value' => '25000.00',
            'effective_from' => '2026-10-01',
        ])->assertSessionHasErrors('effective_from');

        $this->assertDatabaseCount('payroll_policy_versions', 1);
    }

    public function test_non_finance_user_cannot_manage_payroll_policy(): void
    {
        $employeeUser = $this->user(UserRole::Employee, 'employee@example.test');

        $this->actingAs($employeeUser)
            ->get('/finance/payroll-policies')
            ->assertForbidden();
    }

    public function test_employee_can_only_view_own_finalized_payslip(): void
    {
        [$finance, $employeeUser, $employee, $otherUser, $otherEmployee] = $this->workforce();

        $ownPayroll = $this->payroll($finance, $employee, 'Oktober 2026', true);
        $otherPayroll = $this->payroll($finance, $otherEmployee, 'November 2026', true, '2026-11-01', '2026-11-30');

        PayrollItem::create([
            'payroll_id' => $ownPayroll->id,
            'type' => 'ALLOWANCE',
            'code' => 'MEAL',
            'name' => 'Tunjangan Makan',
            'amount' => '300000.00',
            'source_type' => 'MANUAL',
        ]);

        $this->actingAs($employeeUser)
            ->get('/my/payslips')
            ->assertOk()
            ->assertSee('Oktober 2026')
            ->assertDontSee('November 2026');

        $this->actingAs($employeeUser)
            ->get('/my/payslips/'.$ownPayroll->id)
            ->assertOk()
            ->assertSee('SLIP GAJI')
            ->assertSee('Tunjangan Makan')
            ->assertSee('Cetak / Simpan PDF');

        $this->actingAs($employeeUser)
            ->get('/my/payslips/'.$otherPayroll->id)
            ->assertNotFound();

        $this->actingAs($otherUser)
            ->get('/my/payslips/'.$ownPayroll->id)
            ->assertNotFound();
    }

    public function test_draft_payroll_is_not_exposed_as_payslip(): void
    {
        [$finance, $employeeUser, $employee] = $this->workforce();

        $draft = $this->payroll($finance, $employee, 'Oktober 2026', false);

        $this->actingAs($employeeUser)
            ->get('/my/payslips/'.$draft->id)
            ->assertNotFound();

        $this->actingAs($finance)
            ->get('/finance/payslip/'.$draft->id)
            ->assertNotFound();
    }

    public function test_finance_can_view_finalized_payslip(): void
    {
        [$finance, , $employee] = $this->workforce();
        $payroll = $this->payroll($finance, $employee, 'Oktober 2026', true);

        $this->actingAs($finance)
            ->get('/finance/payslip/'.$payroll->id)
            ->assertOk()
            ->assertSee('SLIP GAJI')
            ->assertSee($employee->nip)
            ->assertSee('Cetak / Simpan PDF');
    }

    private function workforce(): array
    {
        $finance = $this->user(UserRole::Finance, 'finance@example.test');

        $department = Department::create([
            'code' => 'PRD',
            'name' => 'Produksi',
            'is_active' => true,
        ]);

        $employeeUser = $this->user(UserRole::Employee, 'employee@example.test');
        $employee = Employee::create([
            'nip' => 'EMP001',
            'name' => 'Pegawai Satu',
            'department_id' => $department->id,
            'user_id' => $employeeUser->id,
            'join_date' => '2026-01-01',
            'employment_status' => 'active',
            'attendance_pin_hash' => Hash::make('123456'),
            'is_active' => true,
        ]);

        $otherUser = $this->user(UserRole::Employee, 'employee2@example.test');
        $otherEmployee = Employee::create([
            'nip' => 'EMP002',
            'name' => 'Pegawai Dua',
            'department_id' => $department->id,
            'user_id' => $otherUser->id,
            'join_date' => '2026-01-01',
            'employment_status' => 'active',
            'attendance_pin_hash' => Hash::make('654321'),
            'is_active' => true,
        ]);

        return [$finance, $employeeUser, $employee, $otherUser, $otherEmployee];
    }

    private function payroll(
        User $finance,
        Employee $employee,
        string $periodName,
        bool $finalized,
        string $periodStart = '2026-10-01',
        string $periodEnd = '2026-10-31'
    ): Payroll {
        $compensation = EmployeeCompensation::create([
            'employee_id' => $employee->id,
            'effective_from' => '2026-01-01',
            'base_salary' => '5000000.00',
            'currency' => 'IDR',
        ]);

        $period = PayrollPeriod::create([
            'name' => $periodName,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'status' => $finalized ? 'FINALIZED' : 'DRAFT',
            'created_by' => $finance->id,
        ]);

        return Payroll::create([
            'payroll_period_id' => $period->id,
            'employee_id' => $employee->id,
            'employee_compensation_id' => $compensation->id,
            'base_salary_snapshot' => '5000000.00',
            'currency' => 'IDR',
            'scheduled_days' => 20,
            'present_days' => 20,
            'late_days' => 0,
            'late_minutes' => 0,
            'absent_days' => 0,
            'leave_days' => 0,
            'permit_days' => 0,
            'incomplete_days' => 0,
            'approved_overtime_minutes' => 0,
            'total_allowance' => '300000.00',
            'gross_pay' => '5300000.00',
            'total_deduction' => '100000.00',
            'net_pay' => '5200000.00',
            'status' => $finalized ? 'FINALIZED' : 'DRAFT',
            'generated_at' => now(),
            'generated_by' => $finance->id,
            'finalized_at' => $finalized ? now() : null,
            'finalized_by' => $finalized ? $finance->id : null,
        ]);
    }

    private function user(UserRole $role, string $email): User
    {
        return User::create([
            'name' => $role->value,
            'email' => $email,
            'password' => 'VerySecret123!',
            'role' => $role,
            'is_active' => true,
        ]);
    }
}
