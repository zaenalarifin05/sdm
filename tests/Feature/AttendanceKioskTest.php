<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Shift;
use App\Models\ShiftSchedule;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AttendanceKioskTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_day_shift_check_in_and_check_out_use_same_attendance(): void
    {
        [$employee] = $this->makeScheduledEmployee('2026-10-02', false);

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-02 07:01:00', 'Asia/Jakarta'));

        $this->post('/attendance', ['nip' => $employee->nip, 'pin' => '123456'])
            ->assertRedirect(route('attendance.create'));

        $attendance = Attendance::firstOrFail();
        $this->assertSame('checked_in', $attendance->state);
        $this->assertNull($attendance->check_out_at);

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-02 15:02:00', 'Asia/Jakarta'));

        $this->post('/attendance', ['nip' => $employee->nip, 'pin' => '123456'])
            ->assertRedirect(route('attendance.create'));

        $attendance->refresh();
        $this->assertSame('completed', $attendance->state);
        $this->assertNotNull($attendance->check_out_at);
        $this->assertDatabaseCount('attendances', 1);
    }

    public function test_shift_three_checkout_next_day_keeps_original_work_date(): void
    {
        [$employee] = $this->makeScheduledEmployee('2026-10-02', true);

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-02 23:05:00', 'Asia/Jakarta'));
        $this->post('/attendance', ['nip' => $employee->nip, 'pin' => '123456'])->assertRedirect();

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-03 07:02:00', 'Asia/Jakarta'));
        $this->post('/attendance', ['nip' => $employee->nip, 'pin' => '123456'])->assertRedirect();

        $attendance = Attendance::firstOrFail();

        $this->assertSame('2026-10-02', $attendance->work_date->format('Y-m-d'));
        $this->assertSame('completed', $attendance->state);
        $this->assertDatabaseCount('attendances', 1);
    }

    public function test_shift_three_can_check_in_after_midnight_against_previous_work_date(): void
    {
        [$employee] = $this->makeScheduledEmployee('2026-10-02', true);

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-03 00:30:00', 'Asia/Jakarta'));

        $this->post('/attendance', ['nip' => $employee->nip, 'pin' => '123456'])
            ->assertRedirect(route('attendance.create'));

        $attendance = Attendance::firstOrFail();

        $this->assertSame('2026-10-02', $attendance->work_date->format('Y-m-d'));
        $this->assertSame('checked_in', $attendance->state);
    }

    public function test_invalid_pin_does_not_create_attendance(): void
    {
        [$employee] = $this->makeScheduledEmployee('2026-10-02', false);

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-02 07:01:00', 'Asia/Jakarta'));

        $this->post('/attendance', ['nip' => $employee->nip, 'pin' => '999999'])
            ->assertSessionHasErrors('nip');

        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_third_punch_after_completed_attendance_is_rejected(): void
    {
        [$employee] = $this->makeScheduledEmployee('2026-10-02', false);

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-02 07:01:00', 'Asia/Jakarta'));
        $this->post('/attendance', ['nip' => $employee->nip, 'pin' => '123456']);

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-02 15:02:00', 'Asia/Jakarta'));
        $this->post('/attendance', ['nip' => $employee->nip, 'pin' => '123456']);

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-02 15:05:00', 'Asia/Jakarta'));
        $this->post('/attendance', ['nip' => $employee->nip, 'pin' => '123456'])
            ->assertSessionHasErrors('nip');

        $this->assertDatabaseCount('attendances', 1);
    }

    private function makeScheduledEmployee(string $workDate, bool $nightShift): array
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
            'code' => $nightShift ? 'SHIFT-3' : 'SHIFT-1',
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
