<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeCompensation;
use App\Models\Payroll;
use App\Models\PayrollPeriod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PayrollEngineFrameworkTest extends TestCase
{
    use RefreshDatabase;

    public function test_manual_allowance_and_deduction_recalculate_gross_and_net_pay(): void
    {
        [$finance, $payroll] = $this->draftPayroll('5000000.00');

        $this->actingAs($finance)->post('/finance/payroll-entry/'.$payroll->id.'/items', [
            'type' => 'ALLOWANCE',
            'code' => 'MEAL',
            'name' => 'Tunjangan Makan',
            'amount' => '500000.00',
        ])->assertRedirect();

        $this->actingAs($finance)->post('/finance/payroll-entry/'.$payroll->id.'/items', [
            'type' => 'DEDUCTION',
            'code' => 'LOAN',
            'name' => 'Potongan Pinjaman',
            'amount' => '200000.00',
        ])->assertRedirect();

        $payroll->refresh();

        $this->assertSame('500000.00', $payroll->total_allowance);
        $this->assertSame('5500000.00', $payroll->gross_pay);
        $this->assertSame('200000.00', $payroll->total_deduction);
        $this->assertSame('5300000.00', $payroll->net_pay);
        $this->assertDatabaseCount('payroll_items', 2);
    }

    public function test_negative_or_zero_manual_component_is_rejected(): void
    {
        [$finance, $payroll] = $this->draftPayroll();

        $this->actingAs($finance)->post('/finance/payroll-entry/'.$payroll->id.'/items', [
            'type' => 'DEDUCTION',
            'code' => 'INVALID',
            'name' => 'Invalid',
            'amount' => '0',
        ])->assertSessionHasErrors('amount');

        $this->assertDatabaseCount('payroll_items', 0);
    }

    public function test_finalize_is_blocked_when_absent_policy_is_not_configured(): void
    {
        [$finance, $payroll, $period] = $this->draftPayroll();
        $payroll->update(['absent_days' => 1]);

        $this->actingAs($finance)
            ->post('/finance/payroll/'.$period->id.'/finalize')
            ->assertSessionHasErrors('payroll_period');

        $this->assertSame('DRAFT', $period->fresh()->status);
        $this->assertSame('DRAFT', $payroll->fresh()->status);
    }

    public function test_finalize_is_blocked_when_late_policy_is_not_configured(): void
    {
        [$finance, $payroll, $period] = $this->draftPayroll();
        $payroll->update(['late_days' => 1, 'late_minutes' => 20]);

        $this->actingAs($finance)
            ->post('/finance/payroll/'.$period->id.'/finalize')
            ->assertSessionHasErrors('payroll_period');

        $this->assertSame('DRAFT', $period->fresh()->status);
    }

    public function test_finalize_is_blocked_when_overtime_policy_is_not_configured(): void
    {
        [$finance, $payroll, $period] = $this->draftPayroll();
        $payroll->update(['approved_overtime_minutes' => 120]);

        $this->actingAs($finance)
            ->post('/finance/payroll/'.$period->id.'/finalize')
            ->assertSessionHasErrors('payroll_period');

        $this->assertSame('DRAFT', $period->fresh()->status);
    }

    public function test_finalize_is_blocked_when_attendance_is_incomplete(): void
    {
        [$finance, $payroll, $period] = $this->draftPayroll();
        $payroll->update(['incomplete_days' => 1]);

        $this->actingAs($finance)
            ->post('/finance/payroll/'.$period->id.'/finalize')
            ->assertSessionHasErrors('payroll_period');

        $this->assertSame('DRAFT', $period->fresh()->status);
    }

    public function test_clean_payroll_can_be_finalized_and_then_is_immutable(): void
    {
        [$finance, $payroll, $period] = $this->draftPayroll();

        $this->actingAs($finance)->post('/finance/payroll-entry/'.$payroll->id.'/items', [
            'type' => 'ALLOWANCE',
            'code' => 'MEAL',
            'name' => 'Tunjangan Makan',
            'amount' => '300000.00',
        ])->assertRedirect();

        $this->actingAs($finance)
            ->post('/finance/payroll/'.$period->id.'/finalize')
            ->assertRedirect(route('finance.payroll.show', $period));

        $this->assertSame('FINALIZED', $period->fresh()->status);
        $this->assertSame('FINALIZED', $payroll->fresh()->status);
        $this->assertNotNull($payroll->fresh()->finalized_at);
        $this->assertSame($finance->id, $payroll->fresh()->finalized_by);

        $this->actingAs($finance)->post('/finance/payroll-entry/'.$payroll->id.'/items', [
            'type' => 'ALLOWANCE',
            'code' => 'AFTER_FINAL',
            'name' => 'Tidak Boleh',
            'amount' => '100000.00',
        ])->assertSessionHasErrors('payroll');

        $this->assertDatabaseCount('payroll_items', 1);
    }

    private function draftPayroll(string $baseSalary = '5000000.00'): array
    {
        $finance = User::create([
            'name' => 'Finance',
            'email' => uniqid('finance-', true).'@example.test',
            'password' => 'VerySecret123!',
            'role' => UserRole::Finance,
            'is_active' => true,
        ]);

        $department = Department::create([
            'code' => 'PRD',
            'name' => 'Produksi',
            'is_active' => true,
        ]);

        $employee = Employee::create([
            'nip' => uniqid('EMP'),
            'name' => 'Pegawai',
            'department_id' => $department->id,
            'join_date' => '2026-01-01',
            'employment_status' => 'active',
            'attendance_pin_hash' => Hash::make('123456'),
            'is_active' => true,
        ]);

        $compensation = EmployeeCompensation::create([
            'employee_id' => $employee->id,
            'effective_from' => '2026-01-01',
            'base_salary' => $baseSalary,
            'currency' => 'IDR',
        ]);

        $period = PayrollPeriod::create([
            'name' => 'Oktober 2026',
            'period_start' => '2026-10-01',
            'period_end' => '2026-10-31',
            'status' => 'DRAFT',
            'created_by' => $finance->id,
        ]);

        $payroll = Payroll::create([
            'payroll_period_id' => $period->id,
            'employee_id' => $employee->id,
            'employee_compensation_id' => $compensation->id,
            'base_salary_snapshot' => $baseSalary,
            'currency' => 'IDR',
            'scheduled_days' => 0,
            'present_days' => 0,
            'late_days' => 0,
            'late_minutes' => 0,
            'absent_days' => 0,
            'leave_days' => 0,
            'permit_days' => 0,
            'incomplete_days' => 0,
            'approved_overtime_minutes' => 0,
            'total_allowance' => '0.00',
            'gross_pay' => $baseSalary,
            'total_deduction' => '0.00',
            'net_pay' => $baseSalary,
            'status' => 'DRAFT',
            'generated_at' => now(),
            'generated_by' => $finance->id,
        ]);

        return [$finance, $payroll, $period, $employee];
    }
}
