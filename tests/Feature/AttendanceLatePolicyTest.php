<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Shift;
use App\Models\ShiftSchedule;
use App\Services\Attendance\ProcessAttendanceForWorkDate;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AttendanceLatePolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_check_in_at_exactly_fifteen_minutes_late_is_present(): void
    {
        [$employee] = $this->schedule();

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-02 07:15:00', 'Asia/Jakarta'));
        $this->post('/attendance', ['nip' => $employee->nip, 'pin' => '123456']);

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-02 15:00:00', 'Asia/Jakarta'));
        $this->post('/attendance', ['nip' => $employee->nip, 'pin' => '123456']);

        $attendance = Attendance::firstOrFail();

        $this->assertSame(15, $attendance->late_minutes);
        $this->assertSame('PRESENT', $attendance->attendance_status);
    }

    public function test_check_in_at_sixteen_minutes_late_is_late(): void
    {
        [$employee] = $this->schedule();

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-02 07:16:00', 'Asia/Jakarta'));
        $this->post('/attendance', ['nip' => $employee->nip, 'pin' => '123456']);

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-02 15:00:00', 'Asia/Jakarta'));
        $this->post('/attendance', ['nip' => $employee->nip, 'pin' => '123456']);

        $attendance = Attendance::firstOrFail();

        $this->assertSame(16, $attendance->late_minutes);
        $this->assertSame('LATE', $attendance->attendance_status);
    }

    public function test_batch_processing_marks_completed_late_attendance_as_late(): void
    {
        [$employee, $schedule] = $this->schedule();
        $scheduledStart = CarbonImmutable::parse('2026-10-02 07:00:00', 'Asia/Jakarta');

        Attendance::create([
            'employee_id' => $employee->id,
            'shift_schedule_id' => $schedule->id,
            'work_date' => '2026-10-02',
            'scheduled_start_at' => $scheduledStart,
            'scheduled_end_at' => $scheduledStart->addHours(8),
            'check_in_at' => $scheduledStart->addMinutes(17),
            'check_out_at' => $scheduledStart->addHours(8),
            'state' => 'completed',
        ]);

        app(ProcessAttendanceForWorkDate::class)->execute(
            '2026-10-02',
            CarbonImmutable::parse('2026-10-02 16:00:00', 'Asia/Jakarta')
        );

        $attendance = Attendance::firstOrFail();

        $this->assertSame(17, $attendance->late_minutes);
        $this->assertSame('LATE', $attendance->attendance_status);
    }

    public function test_incomplete_attendance_keeps_incomplete_status_but_records_late_minutes(): void
    {
        [$employee, $schedule] = $this->schedule();
        $scheduledStart = CarbonImmutable::parse('2026-10-02 07:00:00', 'Asia/Jakarta');

        Attendance::create([
            'employee_id' => $employee->id,
            'shift_schedule_id' => $schedule->id,
            'work_date' => '2026-10-02',
            'scheduled_start_at' => $scheduledStart,
            'scheduled_end_at' => $scheduledStart->addHours(8),
            'check_in_at' => $scheduledStart->addMinutes(20),
            'state' => 'checked_in',
        ]);

        app(ProcessAttendanceForWorkDate::class)->execute(
            '2026-10-02',
            CarbonImmutable::parse('2026-10-02 16:00:00', 'Asia/Jakarta')
        );

        $attendance = Attendance::firstOrFail();

        $this->assertSame(20, $attendance->late_minutes);
        $this->assertSame('INCOMPLETE', $attendance->attendance_status);
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    private function schedule(): array
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
            'employment_status' => 'active',
            'attendance_pin_hash' => Hash::make('123456'),
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

        $schedule = ShiftSchedule::create([
            'employee_id' => $employee->id,
            'shift_id' => $shift->id,
            'work_date' => '2026-10-02',
            'status' => 'scheduled',
        ]);

        return [$employee, $schedule, $shift];
    }
}
