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

class PayrollPolicyEngineTest extends TestCase
{
    use RefreshDatabase;

    public function test_policy_preview_is_idempotent_and_preserves_manual_items(): void
    {
        [$finance, $payroll, $period] = $this->draftPayroll();

        $payroll->update([
            'absent_days' => 2,
            'late_days' => 1,
            'late_minutes' => 30,
            'approved_overtime_minutes' => 90,
        ]);

        PayrollItem::create([
            'payroll_id' => $payroll->id,
            'type' => 'ALLOWANCE',
            'code' => 'MANUAL_BONUS',
            'name' => 'Bonus Manual',
            'amount' => '50000.00',
            'source_type' => 'MANUAL',
        ]);

        PayrollItem::create([
            'payroll_id' => $payroll->id,
            'type' => 'DEDUCTION',
            'code' => 'MANUAL_LOAN',
            'name' => 'Pinjaman',
            'amount' => '20000.00',
            'source_type' => 'MANUAL',
        ]);

        $this->policy($finance, 'absent_deduction', 'fixed_per_day', '100000.00');
        $this->policy($finance, 'late_deduction', 'fixed_per_minute', '1000.00');
        $this->policy($finance, 'overtime_pay', 'fixed_per_hour', '60000.00');

        $this->actingAs($finance)
            ->post('/finance/payroll/'.$period->id.'/apply-policies')
            ->assertRedirect(route('finance.payroll.show', $period));

        $this->actingAs($finance)
            ->post('/finance/payroll/'.$period->id.'/apply-policies')
            ->assertRedirect(route('finance.payroll.show', $period));

        $payroll->refresh();

        $this->assertDatabaseCount('payroll_items', 5);
        $this->assertSame(2, PayrollItem::where('source_type', 'MANUAL')->count());
        $this->assertSame(3, PayrollItem::where('source_type', 'POLICY')->count());
        $this->assertSame(3, PayrollItem::where('source_type', 'POLICY')->whereNotNull('source_id')->count());

        $this->assertDatabaseHas('payroll_items', [
            'payroll_id' => $payroll->id,
            'code' => 'ABSENT_DEDUCTION',
            'amount' => '200000',
            'source_type' => 'POLICY',
        ]);

        $this->assertDatabaseHas('payroll_items', [
            'payroll_id' => $payroll->id,
            'code' => 'LATE_DEDUCTION',
            'amount' => '30000',
            'source_type' => 'POLICY',
        ]);

        $this->assertDatabaseHas('payroll_items', [
            'payroll_id' => $payroll->id,
            'code' => 'OVERTIME_PAY',
            'amount' => '90000',
            'source_type' => 'POLICY',
        ]);

        $this->assertSame('140000.00', $payroll->total_allowance);
        $this->assertSame('5140000.00', $payroll->gross_pay);
        $this->assertSame('250000.00', $payroll->total_deduction);
        $this->assertSame('4890000.00', $payroll->net_pay);
    }

    public function test_salary_divisor_absent_policy_is_supported(): void
    {
        [$finance, $payroll, $period] = $this->draftPayroll('5000000.00');

        $payroll->update(['absent_days' => 2]);

        $this->policy($finance, 'absent_deduction', 'salary_divisor_per_day', '25');

        $this->actingAs($finance)
            ->post('/finance/payroll/'.$period->id.'/apply-policies')
            ->assertRedirect();

        $this->assertDatabaseHas('payroll_items', [
            'payroll_id' => $payroll->id,
            'code' => 'ABSENT_DEDUCTION',
            'amount' => '400000',
            'source_type' => 'POLICY',
        ]);

        $this->assertSame('4600000.00', $payroll->fresh()->net_pay);
    }

    public function test_invalid_enabled_policy_is_rejected_instead_of_silently_ignored(): void
    {
        [$finance, $payroll, $period] = $this->draftPayroll();

        $payroll->update(['absent_days' => 1]);

        PayrollPolicyVersion::create([
            'policy_key' => 'absent_deduction',
            'enabled' => true,
            'mode' => null,
            'value' => null,
            'effective_from' => '2026-10-01',
            'created_by' => $finance->id,
        ]);

        $this->actingAs($finance)
            ->post('/finance/payroll/'.$period->id.'/apply-policies')
            ->assertSessionHasErrors('payroll_policy');

        $this->assertDatabaseCount('payroll_items', 0);
        $this->assertSame('5000000.00', $payroll->fresh()->net_pay);
    }

    public function test_finalize_applies_valid_policies_before_locking_payroll(): void
    {
        [$finance, $payroll, $period] = $this->draftPayroll();

        $payroll->update([
            'absent_days' => 1,
            'late_days' => 1,
            'late_minutes' => 20,
            'approved_overtime_minutes' => 60,
        ]);

        $this->policy($finance, 'absent_deduction', 'fixed_per_day', '100000.00');
        $this->policy($finance, 'late_deduction', 'fixed_per_incident', '25000.00');
        $this->policy($finance, 'overtime_pay', 'fixed_per_minute', '1000.00');

        $this->actingAs($finance)
            ->post('/finance/payroll/'.$period->id.'/finalize')
            ->assertRedirect(route('finance.payroll.show', $period));

        $payroll->refresh();

        $this->assertSame('FINALIZED', $payroll->status);
        $this->assertSame('FINALIZED', $period->fresh()->status);
        $this->assertSame(3, PayrollItem::where('source_type', 'POLICY')->count());
        $this->assertSame('5060000.00', $payroll->gross_pay);
        $this->assertSame('125000.00', $payroll->total_deduction);
        $this->assertSame('4935000.00', $payroll->net_pay);
    }

    private function policy(
        User $finance,
        string $key,
        string $mode,
        string $value,
        string $effectiveFrom = '2026-10-01'
    ): PayrollPolicyVersion {
        return PayrollPolicyVersion::create([
            'policy_key' => $key,
            'enabled' => true,
            'mode' => $mode,
            'value' => $value,
            'effective_from' => $effectiveFrom,
            'created_by' => $finance->id,
        ]);
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

        return [$finance, $payroll, $period];
    }
}
