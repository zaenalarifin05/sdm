<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Attendance;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeCompensation;
use App\Models\OvertimeRequest;
use App\Models\Payroll;
use App\Models\PayrollPeriod;
use App\Models\Shift;
use App\Models\ShiftSchedule;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PayrollFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_finance_user_cannot_access_payroll(): void
    {
        $employeeUser = User::create([
            'name' => 'Employee',
            'email' => 'employee@example.test',
            'password' => 'VerySecret123!',
            'role' => UserRole::Employee,
            'is_active' => true,
        ]);

        $this->actingAs($employeeUser)
            ->get('/finance/payroll')
            ->assertForbidden();
    }

    public function test_finance_can_create_non_overlapping_payroll_period(): void
    {
        $finance = $this->finance();

        $this->actingAs($finance)->post('/finance/payroll', [
            'name' => 'Oktober 2026',
            'period_start' => '2026-10-01',
            'period_end' => '2026-10-31',
        ])->assertRedirect();

        $this->actingAs($finance)->post('/finance/payroll', [
            'name' => 'Overlap',
            'period_start' => '2026-10-15',
            'period_end' => '2026-11-14',
        ])->assertSessionHasErrors('period_start');

        $this->assertDatabaseCount('payroll_periods', 1);
    }

    public function test_payroll_generation_requires_effective_compensation(): void
    {
        [$employee] = $this->employeeWithDepartment();
        $finance = $this->finance();

        $period = PayrollPeriod::create([
            'name' => 'Oktober 2026',
            'period_start' => '2026-10-01',
            'period_end' => '2026-10-31',
            'status' => 'DRAFT',
            'created_by' => $finance->id,
        ]);

        $this->actingAs($finance)
            ->post('/finance/payroll/'.$period->id.'/generate')
            ->assertSessionHasErrors('payroll_period');

        $this->assertDatabaseCount('payrolls', 0);
    }

    public function test_payroll_generation_requires_all_scheduled_attendance_processed(): void
    {
        [$employee, $shift] = $this->employeeWithDepartmentAndShift();
        $finance = $this->finance();

        EmployeeCompensation::create([
            'employee_id' => $employee->id,
            'effective_from' => '2026-09-01',
            'base_salary' => '5000000.00',
            'currency' => 'IDR',
        ]);

        ShiftSchedule::create([
            'employee_id' => $employee->id,
            'shift_id' => $shift->id,
            'work_date' => '2026-10-10',
            'status' => 'scheduled',
        ]);

        $period = PayrollPeriod::create([
            'name' => 'Oktober 2026',
            'period_start' => '2026-10-01',
            'period_end' => '2026-10-31',
            'status' => 'DRAFT',
            'created_by' => $finance->id,
        ]);

        $this->actingAs($finance)
            ->post('/finance/payroll/'.$period->id.'/generate')
            ->assertSessionHasErrors('payroll_period');

        $this->assertDatabaseCount('payrolls', 0);
    }

    public function test_payroll_draft_snapshots_compensation_attendance_and_approved_overtime(): void
    {
        [$employee, $shift] = $this->employeeWithDepartmentAndShift();
        $finance = $this->finance();

        $oldCompensation = EmployeeCompensation::create([
            'employee_id' => $employee->id,
            'effective_from' => '2026-09-01',
            'base_salary' => '5000000.00',
            'currency' => 'IDR',
            'notes' => 'Gaji efektif September',
        ]);

        EmployeeCompensation::create([
            'employee_id' => $employee->id,
            'effective_from' => '2026-11-01',
            'base_salary' => '6000000.00',
            'currency' => 'IDR',
            'notes' => 'Kenaikan November',
        ]);

        $present = $this->schedule($employee, $shift, '2026-10-05');
        $late = $this->schedule($employee, $shift, '2026-10-06');
        $absent = $this->schedule($employee, $shift, '2026-10-07');
        $leave = $this->schedule($employee, $shift, '2026-10-08');

        $this->attendance($employee, $present, 'PRESENT', 0);
        $this->attendance($employee, $late, 'LATE', 20);
        $this->attendance($employee, $absent, 'ABSENT', null);
        $this->attendance($employee, $leave, 'LEAVE', null);

        OvertimeRequest::create([
            'employee_id' => $employee->id,
            'shift_schedule_id' => $present->id,
            'work_date' => '2026-10-05',
            'start_at' => CarbonImmutable::parse('2026-10-05 15:00', 'Asia/Jakarta'),
            'end_at' => CarbonImmutable::parse('2026-10-05 17:00', 'Asia/Jakarta'),
            'duration_minutes' => 120,
            'reason' => 'Approved overtime',
            'status' => 'APPROVED',
        ]);

        OvertimeRequest::create([
            'employee_id' => $employee->id,
            'shift_schedule_id' => $late->id,
            'work_date' => '2026-10-06',
            'start_at' => CarbonImmutable::parse('2026-10-06 15:00', 'Asia/Jakarta'),
            'end_at' => CarbonImmutable::parse('2026-10-06 16:00', 'Asia/Jakarta'),
            'duration_minutes' => 60,
            'reason' => 'Pending overtime',
            'status' => 'PENDING_HR',
        ]);

        $period = PayrollPeriod::create([
            'name' => 'Oktober 2026',
            'period_start' => '2026-10-01',
            'period_end' => '2026-10-31',
            'status' => 'DRAFT',
            'created_by' => $finance->id,
        ]);

        $this->actingAs($finance)
            ->post('/finance/payroll/'.$period->id.'/generate')
            ->assertRedirect(route('finance.payroll.show', $period));

        $payroll = Payroll::firstOrFail();

        $this->assertSame($oldCompensation->id, $payroll->employee_compensation_id);
        $this->assertSame('5000000.00', $payroll->base_salary_snapshot);
        $this->assertSame(4, $payroll->scheduled_days);
        $this->assertSame(1, $payroll->present_days);
        $this->assertSame(1, $payroll->late_days);
        $this->assertSame(20, $payroll->late_minutes);
        $this->assertSame(1, $payroll->absent_days);
        $this->assertSame(1, $payroll->leave_days);
        $this->assertSame(0, $payroll->permit_days);
        $this->assertSame(0, $payroll->incomplete_days);
        $this->assertSame(120, $payroll->approved_overtime_minutes);
        $this->assertSame('DRAFT', $payroll->status);
        $this->assertDatabaseCount('payroll_items', 0);
    }

    private function finance(): User
    {
        return User::create([
            'name' => 'Finance',
            'email' => uniqid('finance-', true).'@example.test',
            'password' => 'VerySecret123!',
            'role' => UserRole::Finance,
            'is_active' => true,
        ]);
    }

    private function employeeWithDepartment(): array
    {
        $department = Department::create([
            'code' => 'PRD',
            'name' => 'Produksi',
            'is_active' => true,
        ]);

        $employee = Employee::create([
            'nip' => 'EMP001',
            'name' => 'Pegawai Satu',
            'department_id' => $department->id,
            'join_date' => '2026-01-01',
            'employment_status' => 'active',
            'attendance_pin_hash' => Hash::make('123456'),
            'is_active' => true,
        ]);

        return [$employee, $department];
    }

    private function employeeWithDepartmentAndShift(): array
    {
        [$employee, $department] = $this->employeeWithDepartment();

        $shift = Shift::create([
            'code' => 'SHIFT-1',
            'name' => 'Shift 1',
            'start_time' => '07:00',
            'end_time' => '15:00',
            'crosses_midnight' => false,
            'is_active' => true,
        ]);

        return [$employee, $shift, $department];
    }

    private function schedule(Employee $employee, Shift $shift, string $date): ShiftSchedule
    {
        return ShiftSchedule::create([
            'employee_id' => $employee->id,
            'shift_id' => $shift->id,
            'work_date' => $date,
            'status' => 'scheduled',
        ]);
    }

    private function attendance(
        Employee $employee,
        ShiftSchedule $schedule,
        string $status,
        ?int $lateMinutes
    ): Attendance {
        $start = CarbonImmutable::parse(
            $schedule->work_date->format('Y-m-d').' 07:00',
            'Asia/Jakarta'
        );

        $hasPunch = in_array($status, ['PRESENT', 'LATE', 'INCOMPLETE'], true);

        return Attendance::create([
            'employee_id' => $employee->id,
            'shift_schedule_id' => $schedule->id,
            'work_date' => $schedule->work_date,
            'scheduled_start_at' => $start,
            'scheduled_end_at' => $start->addHours(8),
            'check_in_at' => $hasPunch ? $start->addMinutes($lateMinutes ?? 0) : null,
            'check_out_at' => in_array($status, ['PRESENT', 'LATE'], true) ? $start->addHours(8) : null,
            'state' => match ($status) {
                'PRESENT', 'LATE' => 'completed',
                'ABSENT' => 'absent',
                'LEAVE', 'PERMIT' => 'excused',
                default => 'incomplete',
            },
            'attendance_status' => $status,
            'late_minutes' => $lateMinutes,
        ]);
    }
}
