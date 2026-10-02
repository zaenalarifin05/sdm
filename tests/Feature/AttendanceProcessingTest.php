<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Attendance;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Shift;
use App\Models\ShiftSchedule;
use App\Models\User;
use App\Services\Attendance\ProcessAttendanceForWorkDate;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AttendanceProcessingTest extends TestCase
{
    use RefreshDatabase;

    public function test_completed_attendance_becomes_present(): void
    {
        [$employee, $schedule] = $this->schedule('2026-10-02', false);
        $start = CarbonImmutable::parse('2026-10-02 07:00:00', 'Asia/Jakarta');

        Attendance::create([
            'employee_id' => $employee->id,
            'shift_schedule_id' => $schedule->id,
            'work_date' => '2026-10-02',
            'scheduled_start_at' => $start,
            'scheduled_end_at' => $start->addHours(8),
            'check_in_at' => $start->addMinute(),
            'check_out_at' => $start->addHours(8),
            'state' => 'completed',
        ]);

        app(ProcessAttendanceForWorkDate::class)->execute(
            '2026-10-02',
            CarbonImmutable::parse('2026-10-02 16:00:00', 'Asia/Jakarta')
        );

        $this->assertDatabaseHas('attendances', [
            'shift_schedule_id' => $schedule->id,
            'attendance_status' => 'PRESENT',
            'state' => 'completed',
        ]);
    }

    public function test_open_attendance_after_shift_end_becomes_incomplete(): void
    {
        [$employee, $schedule] = $this->schedule('2026-10-02', false);
        $start = CarbonImmutable::parse('2026-10-02 07:00:00', 'Asia/Jakarta');

        Attendance::create([
            'employee_id' => $employee->id,
            'shift_schedule_id' => $schedule->id,
            'work_date' => '2026-10-02',
            'scheduled_start_at' => $start,
            'scheduled_end_at' => $start->addHours(8),
            'check_in_at' => $start->addMinute(),
            'state' => 'checked_in',
        ]);

        app(ProcessAttendanceForWorkDate::class)->execute(
            '2026-10-02',
            CarbonImmutable::parse('2026-10-02 16:00:00', 'Asia/Jakarta')
        );

        $this->assertDatabaseHas('attendances', [
            'shift_schedule_id' => $schedule->id,
            'attendance_status' => 'INCOMPLETE',
            'state' => 'incomplete',
        ]);
    }

    public function test_missing_attendance_after_shift_end_becomes_absent(): void
    {
        [, $schedule] = $this->schedule('2026-10-02', false);

        app(ProcessAttendanceForWorkDate::class)->execute(
            '2026-10-02',
            CarbonImmutable::parse('2026-10-02 16:00:00', 'Asia/Jakarta')
        );

        $attendance = Attendance::where('shift_schedule_id', $schedule->id)->firstOrFail();

        $this->assertSame('ABSENT', $attendance->attendance_status);
        $this->assertSame('absent', $attendance->state);
        $this->assertNull($attendance->check_in_at);
        $this->assertNull($attendance->check_out_at);
    }

    public function test_shift_not_yet_ended_is_not_processed(): void
    {
        [, $schedule] = $this->schedule('2026-10-02', false);

        $result = app(ProcessAttendanceForWorkDate::class)->execute(
            '2026-10-02',
            CarbonImmutable::parse('2026-10-02 12:00:00', 'Asia/Jakarta')
        );

        $this->assertFalse($result->first()['processed']);
        $this->assertDatabaseMissing('attendances', [
            'shift_schedule_id' => $schedule->id,
        ]);
    }

    public function test_overnight_shift_becomes_absent_only_after_next_day_end(): void
    {
        [, $schedule] = $this->schedule('2026-10-02', true);

        app(ProcessAttendanceForWorkDate::class)->execute(
            '2026-10-02',
            CarbonImmutable::parse('2026-10-03 06:30:00', 'Asia/Jakarta')
        );

        $this->assertDatabaseMissing('attendances', [
            'shift_schedule_id' => $schedule->id,
        ]);

        app(ProcessAttendanceForWorkDate::class)->execute(
            '2026-10-02',
            CarbonImmutable::parse('2026-10-03 07:01:00', 'Asia/Jakarta')
        );

        $this->assertDatabaseHas('attendances', [
            'shift_schedule_id' => $schedule->id,
            'work_date' => '2026-10-02 00:00:00',
            'attendance_status' => 'ABSENT',
        ]);
    }

    public function test_hr_can_monitor_and_process_but_employee_cannot(): void
    {
        $employeeUser = User::create([
            'name' => 'Employee User',
            'email' => 'employee@example.test',
            'password' => 'Secret123!',
            'role' => UserRole::Employee,
            'is_active' => true,
        ]);

        $hr = User::create([
            'name' => 'HR Admin',
            'email' => 'hr@example.test',
            'password' => 'Secret123!',
            'role' => UserRole::HrAdmin,
            'is_active' => true,
        ]);

        $this->actingAs($employeeUser)
            ->get('/hr/attendance?work_date=2026-10-02')
            ->assertForbidden();

        $this->actingAs($hr)
            ->get('/hr/attendance?work_date=2026-10-02')
            ->assertOk();

        $this->actingAs($employeeUser)
            ->post('/hr/attendance/process', ['work_date' => '2026-10-02'])
            ->assertForbidden();
    }

    private function schedule(string $workDate, bool $nightShift): array
    {
        $department = Department::firstOrCreate(
            ['code' => 'PRD'],
            ['name' => 'Produksi', 'is_active' => true]
        );

        $employee = Employee::create([
            'nip' => 'EMP'.str_replace('-', '', $workDate).($nightShift ? 'N' : 'D'),
            'name' => 'Pegawai',
            'department_id' => $department->id,
            'employment_status' => 'active',
            'attendance_pin_hash' => Hash::make('123456'),
            'is_active' => true,
        ]);

        $shift = Shift::create([
            'code' => $nightShift ? 'SHIFT-3-'.$employee->id : 'SHIFT-1-'.$employee->id,
            'name' => $nightShift ? 'Shift 3' : 'Shift 1',
            'start_time' => $nightShift ? '23:00' : '07:00',
            'end_time' => $nightShift ? '07:00' : '15:00',
            'crosses_midnight' => $nightShift,
            'is_active' => true,
        ]);

        $schedule = ShiftSchedule::create([
            'employee_id' => $employee->id,
            'shift_id' => $shift->id,
            'work_date' => $workDate,
            'status' => 'scheduled',
        ]);

        return [$employee, $schedule, $shift];
    }
}
