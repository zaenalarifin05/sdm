<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Attendance;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Shift;
use App\Models\ShiftSchedule;
use App\Models\TemporaryPermission;
use App\Models\User;
use App\Services\Attendance\ProcessAttendanceForWorkDate;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TemporaryPermissionFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_employee_can_request_manager_approve_and_server_records_out_return_duration(): void
    {
        [$employeeUser, $employee, $managerUser, , $shift] = $this->workforce();
        $schedule = $this->schedule($employee, $shift, '2026-10-12');
        $this->attendance($employee, $schedule, false);

        $this->actingAs($employeeUser)->post('/my/temporary-permissions', [
            'shift_schedule_id' => $schedule->id,
            'reason' => 'Keperluan bank',
        ])->assertRedirect(route('my.temporary-permissions.index'));

        $permission = TemporaryPermission::firstOrFail();
        $this->assertSame('PENDING_MANAGER', $permission->status);

        $this->actingAs($managerUser)
            ->post('/manager/temporary-permissions/'.$permission->id.'/approve', [
                'note' => 'Silakan',
            ])
            ->assertRedirect();

        $this->assertSame('APPROVED', $permission->fresh()->status);

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-12 09:00:00', 'Asia/Jakarta'));

        $this->actingAs($employeeUser)
            ->post('/my/temporary-permissions/'.$permission->id.'/start')
            ->assertRedirect();

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-12 10:15:00', 'Asia/Jakarta'));

        $this->actingAs($employeeUser)
            ->post('/my/temporary-permissions/'.$permission->id.'/return')
            ->assertRedirect();

        $permission->refresh();

        $this->assertSame('COMPLETED', $permission->status);
        $this->assertSame(4500, $permission->duration_seconds);
        $this->assertSame(75, $permission->duration_minutes);
    }

    public function test_manager_cannot_approve_temporary_permission_from_other_department(): void
    {
        [$employeeUser, $employee, , , $shift] = $this->workforce();
        $schedule = $this->schedule($employee, $shift, '2026-10-12');

        $this->actingAs($employeeUser)->post('/my/temporary-permissions', [
            'shift_schedule_id' => $schedule->id,
            'reason' => 'Keperluan pribadi',
        ]);

        $permission = TemporaryPermission::firstOrFail();

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
            ->post('/manager/temporary-permissions/'.$permission->id.'/approve')
            ->assertForbidden();

        $this->assertSame('PENDING_MANAGER', $permission->fresh()->status);
    }

    public function test_accumulated_exactly_120_minutes_remains_present(): void
    {
        [, $employee, , , $shift] = $this->workforce();
        $schedule = $this->schedule($employee, $shift, '2026-10-12');
        $attendance = $this->attendance($employee, $schedule);

        $this->completedPermission($employee, $schedule, '09:00', '10:00');
        $this->completedPermission($employee, $schedule, '12:00', '13:00');

        app(ProcessAttendanceForWorkDate::class)->execute(
            '2026-10-12',
            CarbonImmutable::parse('2026-10-12 16:00:00', 'Asia/Jakarta')
        );

        $attendance->refresh();

        $this->assertSame(120, $attendance->temporary_permission_minutes);
        $this->assertSame('PRESENT', $attendance->attendance_status);
        $this->assertSame('completed', $attendance->state);
    }

    public function test_accumulated_more_than_120_minutes_becomes_absent(): void
    {
        [, $employee, , , $shift] = $this->workforce();
        $schedule = $this->schedule($employee, $shift, '2026-10-12');
        $attendance = $this->attendance($employee, $schedule);

        $this->completedPermission($employee, $schedule, '09:00', '10:00');
        $this->completedPermission($employee, $schedule, '12:00', '13:01');

        app(ProcessAttendanceForWorkDate::class)->execute(
            '2026-10-12',
            CarbonImmutable::parse('2026-10-12 16:00:00', 'Asia/Jakarta')
        );

        $attendance->refresh();

        $this->assertSame(121, $attendance->temporary_permission_minutes);
        $this->assertSame('ABSENT', $attendance->attendance_status);
        $this->assertSame('absent', $attendance->state);
    }

    public function test_open_temporary_permission_at_shift_end_is_incomplete_when_under_limit(): void
    {
        [, $employee, $managerUser, , $shift] = $this->workforce();
        $schedule = $this->schedule($employee, $shift, '2026-10-12');
        $attendance = $this->attendance($employee, $schedule, false);

        TemporaryPermission::create([
            'employee_id' => $employee->id,
            'shift_schedule_id' => $schedule->id,
            'reason' => 'Belum kembali',
            'status' => 'APPROVED',
            'approved_by_user_id' => $managerUser->id,
            'approved_at' => CarbonImmutable::parse('2026-10-12 14:20:00', 'Asia/Jakarta'),
            'out_at' => CarbonImmutable::parse('2026-10-12 14:30:00', 'Asia/Jakarta'),
        ]);

        app(ProcessAttendanceForWorkDate::class)->execute(
            '2026-10-12',
            CarbonImmutable::parse('2026-10-12 16:00:00', 'Asia/Jakarta')
        );

        $attendance->refresh();

        $this->assertSame(30, $attendance->temporary_permission_minutes);
        $this->assertSame('INCOMPLETE', $attendance->attendance_status);
    }

    public function test_reprocessing_existing_absent_attendance_remains_absent(): void
    {
        [, $employee, , , $shift] = $this->workforce();
        $schedule = $this->schedule($employee, $shift, '2026-10-12');

        app(ProcessAttendanceForWorkDate::class)->execute(
            '2026-10-12',
            CarbonImmutable::parse('2026-10-12 16:00:00', 'Asia/Jakarta')
        );

        $attendance = Attendance::firstOrFail();
        $this->assertSame('ABSENT', $attendance->attendance_status);

        app(ProcessAttendanceForWorkDate::class)->execute(
            '2026-10-12',
            CarbonImmutable::parse('2026-10-12 17:00:00', 'Asia/Jakarta')
        );

        $this->assertSame('ABSENT', $attendance->fresh()->attendance_status);
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

    private function attendance(Employee $employee, ShiftSchedule $schedule, bool $withCheckout = true): Attendance
    {
        $start = CarbonImmutable::parse('2026-10-12 07:00:00', 'Asia/Jakarta');

        return Attendance::create([
            'employee_id' => $employee->id,
            'shift_schedule_id' => $schedule->id,
            'work_date' => '2026-10-12',
            'scheduled_start_at' => $start,
            'scheduled_end_at' => $start->addHours(8),
            'check_in_at' => $start,
            'check_out_at' => $withCheckout ? $start->addHours(8) : null,
            'state' => $withCheckout ? 'completed' : 'checked_in',
            'attendance_status' => $withCheckout ? 'PRESENT' : null,
            'late_minutes' => 0,
        ]);
    }

    private function completedPermission(
        Employee $employee,
        ShiftSchedule $schedule,
        string $out,
        string $returned
    ): TemporaryPermission {
        $outAt = CarbonImmutable::parse('2026-10-12 '.$out.':00', 'Asia/Jakarta');
        $returnedAt = CarbonImmutable::parse('2026-10-12 '.$returned.':00', 'Asia/Jakarta');

        return TemporaryPermission::create([
            'employee_id' => $employee->id,
            'shift_schedule_id' => $schedule->id,
            'reason' => 'Izin keluar',
            'status' => 'COMPLETED',
            'out_at' => $outAt,
            'returned_at' => $returnedAt,
            'duration_seconds' => $returnedAt->getTimestamp() - $outAt->getTimestamp(),
        ]);
    }
}
