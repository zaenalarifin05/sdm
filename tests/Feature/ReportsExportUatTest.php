<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Attendance;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeCompensation;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\OvertimeRequest;
use App\Models\Payroll;
use App\Models\PayrollPeriod;
use App\Models\Shift;
use App\Models\ShiftSchedule;
use App\Models\TemporaryPermission;
use App\Models\User;
use App\Support\CsvExport;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class ReportsExportUatTest extends TestCase
{
    use RefreshDatabase;

    public function test_hr_report_filters_attendance_by_department_and_exports_excel_friendly_csv(): void
    {
        [$hr, $finance, $departmentA, $departmentB, $employeeA, $employeeB, $shift] = $this->workforce();

        $scheduleA = $this->schedule($employeeA, $shift, '2026-10-10');
        $scheduleB = $this->schedule($employeeB, $shift, '2026-10-10');

        $this->attendance($employeeA, $scheduleA, 'PRESENT');
        $this->attendance($employeeB, $scheduleB, 'ABSENT');

        $this->actingAs($hr)
            ->get('/hr/reports?from=2026-10-01&to=2026-10-31&department_id='.$departmentA->id)
            ->assertOk()
            ->assertSee('Total Attendance')
            ->assertSee('Present');

        $response = $this->actingAs($hr)
            ->get('/hr/reports/attendance.csv?from=2026-10-01&to=2026-10-31&department_id='.$departmentA->id)
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $csv = $this->streamedContent($response);

        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $this->assertStringContainsString($employeeA->nip, $csv);
        $this->assertStringNotContainsString($employeeB->nip, $csv);
        $this->assertStringContainsString($departmentA->name, $csv);

        $this->actingAs($finance)
            ->get('/hr/reports')
            ->assertForbidden();
    }

    public function test_leave_temporary_permission_and_overtime_exports_contain_operational_data(): void
    {
        [$hr, , $departmentA, , $employeeA, , $shift] = $this->workforce();
        $schedule = $this->schedule($employeeA, $shift, '2026-10-11');

        $leaveType = LeaveType::create([
            'code' => 'PERMIT',
            'name' => 'Izin',
            'category' => 'permit',
            'deduct_balance' => false,
            'is_active' => true,
        ]);

        LeaveRequest::create([
            'employee_id' => $employeeA->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-10-11',
            'end_date' => '2026-10-11',
            'requested_days' => 1,
            'reason' => 'Keperluan administrasi',
            'status' => 'APPROVED',
        ]);

        TemporaryPermission::create([
            'employee_id' => $employeeA->id,
            'shift_schedule_id' => $schedule->id,
            'reason' => 'Ke bank',
            'status' => 'COMPLETED',
            'out_at' => CarbonImmutable::parse('2026-10-11 09:00', 'Asia/Jakarta'),
            'returned_at' => CarbonImmutable::parse('2026-10-11 10:00', 'Asia/Jakarta'),
            'duration_seconds' => 3600,
        ]);

        OvertimeRequest::create([
            'employee_id' => $employeeA->id,
            'shift_schedule_id' => $schedule->id,
            'work_date' => '2026-10-11',
            'start_at' => CarbonImmutable::parse('2026-10-11 15:00', 'Asia/Jakarta'),
            'end_at' => CarbonImmutable::parse('2026-10-11 17:00', 'Asia/Jakarta'),
            'duration_minutes' => 120,
            'reason' => 'Target produksi',
            'status' => 'APPROVED',
        ]);

        $query = '?from=2026-10-01&to=2026-10-31&department_id='.$departmentA->id;

        $leaveCsv = $this->streamedContent(
            $this->actingAs($hr)->get('/hr/reports/leave.csv'.$query)->assertOk()
        );
        $temporaryCsv = $this->streamedContent(
            $this->actingAs($hr)->get('/hr/reports/temporary-permissions.csv'.$query)->assertOk()
        );
        $overtimeCsv = $this->streamedContent(
            $this->actingAs($hr)->get('/hr/reports/overtime.csv'.$query)->assertOk()
        );

        $this->assertStringContainsString('Keperluan administrasi', $leaveCsv);
        $this->assertStringContainsString('permit', $leaveCsv);
        $this->assertStringContainsString('Ke bank', $temporaryCsv);
        $this->assertStringContainsString(',60,', $temporaryCsv);
        $this->assertStringContainsString('Target produksi', $overtimeCsv);
        $this->assertStringContainsString(',120,APPROVED,', $overtimeCsv);
    }

    public function test_payroll_recap_is_finance_only_and_period_specific(): void
    {
        [, $finance, $departmentA, , $employeeA] = $this->workforce();
        $hr = User::query()->where('role', UserRole::HrAdmin)->firstOrFail();

        $october = $this->payroll($finance, $employeeA, 'Oktober 2026', '2026-10-01', '2026-10-31', '5200000.00');
        $november = $this->payroll($finance, $employeeA, 'November 2026', '2026-11-01', '2026-11-30', '5400000.00');

        $this->actingAs($finance)
            ->get('/finance/reports/payroll?payroll_period_id='.$october->payroll_period_id)
            ->assertOk()
            ->assertSee('Oktober 2026')
            ->assertSee('5.200.000');

        $response = $this->actingAs($finance)
            ->get('/finance/reports/payroll.csv?payroll_period_id='.$october->payroll_period_id)
            ->assertOk();

        $csv = $this->streamedContent($response);

        $this->assertStringContainsString('Oktober 2026', $csv);
        $this->assertStringContainsString('5200000.00', $csv);
        $this->assertStringNotContainsString('November 2026', $csv);
        $this->assertStringNotContainsString('5400000.00', $csv);

        $this->actingAs($hr)
            ->get('/finance/reports/payroll')
            ->assertForbidden();

        $this->assertSame($departmentA->id, $employeeA->department_id);
        $this->assertNotSame($october->payroll_period_id, $november->payroll_period_id);
    }

    public function test_csv_formula_like_user_input_is_neutralized(): void
    {
        $this->assertSame("'=2+2", CsvExport::safeCell('=2+2'));
        $this->assertSame("'+SUM(A1:A2)", CsvExport::safeCell('+SUM(A1:A2)'));
        $this->assertSame("'-10", CsvExport::safeCell('-10'));
        $this->assertSame("'@cmd", CsvExport::safeCell('@cmd'));
        $this->assertSame('Normal text', CsvExport::safeCell('Normal text'));
    }

    public function test_report_date_validation_rejects_reversed_range(): void
    {
        [$hr] = $this->workforce();

        $this->actingAs($hr)
            ->get('/hr/reports?from=2026-10-31&to=2026-10-01')
            ->assertSessionHasErrors('to');
    }

    public function test_employee_cannot_access_hr_or_finance_reports(): void
    {
        [, , , , $employeeA] = $this->workforce();
        $employeeUser = User::findOrFail($employeeA->user_id);

        $this->actingAs($employeeUser)->get('/hr/reports')->assertForbidden();
        $this->actingAs($employeeUser)->get('/finance/reports/payroll')->assertForbidden();
    }

    private function workforce(): array
    {
        $hr = $this->user(UserRole::HrAdmin, 'hr@example.test');
        $finance = $this->user(UserRole::Finance, 'finance@example.test');

        $departmentA = Department::create([
            'code' => 'PRD',
            'name' => 'Produksi',
            'is_active' => true,
        ]);

        $departmentB = Department::create([
            'code' => 'QAC',
            'name' => 'Quality',
            'is_active' => true,
        ]);

        $employeeAUser = $this->user(UserRole::Employee, 'employee.a@example.test');
        $employeeA = Employee::create([
            'nip' => 'EMP001',
            'name' => '=HYPERLINK("https://example.test","Pegawai")',
            'department_id' => $departmentA->id,
            'user_id' => $employeeAUser->id,
            'join_date' => '2026-01-01',
            'employment_status' => 'active',
            'attendance_pin_hash' => Hash::make('123456'),
            'is_active' => true,
        ]);

        $employeeBUser = $this->user(UserRole::Employee, 'employee.b@example.test');
        $employeeB = Employee::create([
            'nip' => 'EMP002',
            'name' => 'Pegawai Dua',
            'department_id' => $departmentB->id,
            'user_id' => $employeeBUser->id,
            'join_date' => '2026-01-01',
            'employment_status' => 'active',
            'attendance_pin_hash' => Hash::make('654321'),
            'is_active' => true,
        ]);

        $shift = Shift::create([
            'code' => 'SHIFT-1',
            'name' => 'Shift 1',
            'start_time' => '07:00',
            'end_time' => '15:00',
            'crosses_midnight' => false,
            'is_active' => true,
        ]);

        return [$hr, $finance, $departmentA, $departmentB, $employeeA, $employeeB, $shift];
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

    private function attendance(Employee $employee, ShiftSchedule $schedule, string $status): Attendance
    {
        $start = CarbonImmutable::parse($schedule->work_date->format('Y-m-d').' 07:00', 'Asia/Jakarta');

        return Attendance::create([
            'employee_id' => $employee->id,
            'shift_schedule_id' => $schedule->id,
            'work_date' => $schedule->work_date,
            'scheduled_start_at' => $start,
            'scheduled_end_at' => $start->addHours(8),
            'check_in_at' => $status === 'ABSENT' ? null : $start,
            'check_out_at' => $status === 'ABSENT' ? null : $start->addHours(8),
            'state' => $status === 'ABSENT' ? 'absent' : 'completed',
            'attendance_status' => $status,
            'late_minutes' => 0,
            'temporary_permission_minutes' => 0,
        ]);
    }

    private function payroll(
        User $finance,
        Employee $employee,
        string $name,
        string $start,
        string $end,
        string $netPay
    ): Payroll {
        $compensation = EmployeeCompensation::query()
            ->where('employee_id', $employee->id)
            ->first();

        if (! $compensation) {
            $compensation = EmployeeCompensation::create([
                'employee_id' => $employee->id,
                'effective_from' => '2026-01-01',
                'base_salary' => '5000000.00',
                'currency' => 'IDR',
            ]);
        }

        $period = PayrollPeriod::create([
            'name' => $name,
            'period_start' => $start,
            'period_end' => $end,
            'status' => 'FINALIZED',
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
            'net_pay' => $netPay,
            'status' => 'FINALIZED',
            'generated_at' => now(),
            'generated_by' => $finance->id,
            'finalized_at' => now(),
            'finalized_by' => $finance->id,
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

    private function streamedContent(TestResponse $response): string
    {
        ob_start();
        $response->baseResponse->sendContent();

        return (string) ob_get_clean();
    }
}
