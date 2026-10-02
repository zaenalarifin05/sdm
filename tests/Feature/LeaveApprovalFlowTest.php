<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Attendance;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Shift;
use App\Models\ShiftSchedule;
use App\Models\User;
use App\Services\Attendance\ProcessAttendanceForWorkDate;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LeaveApprovalFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_annual_leave_uses_schedule_days_and_deducts_only_after_hr_final_approval(): void
    {
        [$employeeUser, $employee, $managerUser, $hr, $shift] = $this->workforce();

        $scheduleOne = $this->schedule($employee, $shift, '2026-10-05');
        $scheduleTwo = $this->schedule($employee, $shift, '2026-10-06');

        $annual = LeaveType::create([
            'code' => 'ANNUAL',
            'name' => 'Cuti Tahunan',
            'category' => 'leave',
            'deduct_balance' => true,
            'is_active' => true,
        ]);

        $balance = LeaveBalance::create([
            'employee_id' => $employee->id,
            'leave_type_id' => $annual->id,
            'year' => 2026,
            'allocated_days' => 10,
            'used_days' => 0,
        ]);

        $this->actingAs($employeeUser)->post('/my/leave', [
            'leave_type_id' => $annual->id,
            'start_date' => '2026-10-05',
            'end_date' => '2026-10-06',
            'reason' => 'Keperluan keluarga',
        ])->assertRedirect(route('my.leave.index'));

        $leave = LeaveRequest::firstOrFail();

        $this->assertSame('PENDING_MANAGER', $leave->status);
        $this->assertSame(2, $leave->requested_days);
        $this->assertDatabaseCount('leave_request_days', 2);
        $this->assertSame(0, $balance->fresh()->used_days);

        $this->actingAs($managerUser)
            ->post('/manager/leave/'.$leave->id.'/approve', ['note' => 'Disetujui atasan'])
            ->assertRedirect();

        $this->assertSame('PENDING_HR', $leave->fresh()->status);
        $this->assertSame(0, $balance->fresh()->used_days);

        $this->actingAs($hr)
            ->post('/hr/leave/'.$leave->id.'/approve', ['note' => 'Final HR'])
            ->assertRedirect();

        $this->assertSame('APPROVED', $leave->fresh()->status);
        $this->assertSame(2, $balance->fresh()->used_days);
        $this->assertDatabaseHas('leave_approvals', [
            'leave_request_id' => $leave->id,
            'stage' => 'manager',
            'action' => 'approved',
        ]);
        $this->assertDatabaseHas('leave_approvals', [
            'leave_request_id' => $leave->id,
            'stage' => 'hr',
            'action' => 'approved',
        ]);

        app(ProcessAttendanceForWorkDate::class)->execute(
            '2026-10-05',
            CarbonImmutable::parse('2026-10-05 16:00:00', 'Asia/Jakarta')
        );

        $this->assertDatabaseHas('attendances', [
            'shift_schedule_id' => $scheduleOne->id,
            'attendance_status' => 'LEAVE',
            'state' => 'excused',
        ]);

        $this->assertDatabaseMissing('attendances', [
            'shift_schedule_id' => $scheduleTwo->id,
        ]);
    }

    public function test_manager_cannot_approve_employee_from_other_department(): void
    {
        [$employeeUser, $employee, , $hr, $shift] = $this->workforce();

        $this->schedule($employee, $shift, '2026-10-05');

        $permit = LeaveType::create([
            'code' => 'PERMIT',
            'name' => 'Izin',
            'category' => 'permit',
            'deduct_balance' => false,
            'is_active' => true,
        ]);

        $this->actingAs($employeeUser)->post('/my/leave', [
            'leave_type_id' => $permit->id,
            'start_date' => '2026-10-05',
            'end_date' => '2026-10-05',
            'reason' => 'Keperluan pribadi',
        ]);

        $leave = LeaveRequest::firstOrFail();

        $otherDepartment = Department::create([
            'code' => 'QAC',
            'name' => 'Quality',
            'is_active' => true,
        ]);

        $otherManagerUser = User::create([
            'name' => 'Other Manager',
            'email' => 'other.manager@example.test',
            'password' => 'VerySecret123!',
            'role' => UserRole::Manager,
            'is_active' => true,
        ]);

        $otherManager = Employee::create([
            'nip' => 'MGR002',
            'name' => 'Other Manager',
            'department_id' => $otherDepartment->id,
            'user_id' => $otherManagerUser->id,
            'employment_status' => 'active',
            'attendance_pin_hash' => Hash::make('654321'),
            'is_active' => true,
        ]);

        $otherDepartment->update(['manager_employee_id' => $otherManager->id]);

        $this->actingAs($otherManagerUser)
            ->post('/manager/leave/'.$leave->id.'/approve')
            ->assertForbidden();

        $this->assertSame('PENDING_MANAGER', $leave->fresh()->status);
    }

    public function test_permit_needs_no_balance_and_becomes_permit_after_final_approval(): void
    {
        [$employeeUser, $employee, $managerUser, $hr, $shift] = $this->workforce();

        $schedule = $this->schedule($employee, $shift, '2026-10-07');

        $permit = LeaveType::create([
            'code' => 'PERMIT',
            'name' => 'Izin',
            'category' => 'permit',
            'deduct_balance' => false,
            'is_active' => true,
        ]);

        $this->actingAs($employeeUser)->post('/my/leave', [
            'leave_type_id' => $permit->id,
            'start_date' => '2026-10-07',
            'end_date' => '2026-10-07',
            'reason' => 'Keperluan administratif',
        ])->assertRedirect();

        $leave = LeaveRequest::firstOrFail();

        $this->actingAs($managerUser)
            ->post('/manager/leave/'.$leave->id.'/approve')
            ->assertRedirect();

        $this->actingAs($hr)
            ->post('/hr/leave/'.$leave->id.'/approve')
            ->assertRedirect();

        $this->assertDatabaseCount('leave_balances', 0);

        app(ProcessAttendanceForWorkDate::class)->execute(
            '2026-10-07',
            CarbonImmutable::parse('2026-10-07 16:00:00', 'Asia/Jakarta')
        );

        $this->assertDatabaseHas('attendances', [
            'shift_schedule_id' => $schedule->id,
            'attendance_status' => 'PERMIT',
            'state' => 'excused',
        ]);
    }

    public function test_pending_leave_does_not_replace_absence(): void
    {
        [$employeeUser, $employee, , , $shift] = $this->workforce();
        $schedule = $this->schedule($employee, $shift, '2026-10-08');

        $permit = LeaveType::create([
            'code' => 'PERMIT',
            'name' => 'Izin',
            'category' => 'permit',
            'deduct_balance' => false,
            'is_active' => true,
        ]);

        $this->actingAs($employeeUser)->post('/my/leave', [
            'leave_type_id' => $permit->id,
            'start_date' => '2026-10-08',
            'end_date' => '2026-10-08',
            'reason' => 'Belum final approval',
        ]);

        app(ProcessAttendanceForWorkDate::class)->execute(
            '2026-10-08',
            CarbonImmutable::parse('2026-10-08 16:00:00', 'Asia/Jakarta')
        );

        $this->assertDatabaseHas('attendances', [
            'shift_schedule_id' => $schedule->id,
            'attendance_status' => 'ABSENT',
        ]);
    }

    private function workforce(): array
    {
        $department = Department::create([
            'code' => 'PRD',
            'name' => 'Produksi',
            'is_active' => true,
        ]);

        $managerUser = User::create([
            'name' => 'Supervisor',
            'email' => 'manager@example.test',
            'password' => 'VerySecret123!',
            'role' => UserRole::Manager,
            'is_active' => true,
        ]);

        $manager = Employee::create([
            'nip' => 'MGR001',
            'name' => 'Supervisor',
            'department_id' => $department->id,
            'user_id' => $managerUser->id,
            'employment_status' => 'active',
            'attendance_pin_hash' => Hash::make('123456'),
            'is_active' => true,
        ]);

        $department->update(['manager_employee_id' => $manager->id]);

        $employeeUser = User::create([
            'name' => 'Pegawai',
            'email' => 'employee@example.test',
            'password' => 'VerySecret123!',
            'role' => UserRole::Employee,
            'is_active' => true,
        ]);

        $employee = Employee::create([
            'nip' => 'EMP001',
            'name' => 'Pegawai',
            'department_id' => $department->id,
            'user_id' => $employeeUser->id,
            'employment_status' => 'active',
            'attendance_pin_hash' => Hash::make('123456'),
            'is_active' => true,
        ]);

        $hr = User::create([
            'name' => 'HR Admin',
            'email' => 'hr@example.test',
            'password' => 'VerySecret123!',
            'role' => UserRole::HrAdmin,
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

        return [$employeeUser, $employee, $managerUser, $hr, $shift];
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
}
