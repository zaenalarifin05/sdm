<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Attendance;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\Shift;
use App\Models\ShiftSchedule;
use App\Models\User;
use App\Services\Attendance\ProcessAttendanceForWorkDate;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class HolidayCalendarTest extends TestCase
{
    use RefreshDatabase;

    public function test_hr_can_create_holiday_and_duplicate_date_is_rejected(): void
    {
        $hr = $this->user(UserRole::HrAdmin, 'hr@example.test');

        $this->actingAs($hr)->post('/hr/holidays', [
            'holiday_date' => '2026-12-25',
            'name' => 'Hari Libur Nasional',
            'type' => 'national',
            'reference' => 'Keputusan Pemerintah',
        ])->assertRedirect(route('hr.holidays.index'));

        $this->assertDatabaseHas('holidays', [
            'holiday_date' => '2026-12-25 00:00:00',
            'name' => 'Hari Libur Nasional',
            'type' => 'national',
            'is_active' => true,
        ]);

        $this->actingAs($hr)->post('/hr/holidays', [
            'holiday_date' => '2026-12-25',
            'name' => 'Duplikat',
            'type' => 'company',
        ])->assertSessionHasErrors('holiday_date');
    }

    public function test_employee_cannot_manage_holiday_calendar(): void
    {
        $employee = $this->user(UserRole::Employee, 'employee@example.test');

        $this->actingAs($employee)->get('/hr/holidays')->assertForbidden();

        $this->actingAs($employee)->post('/hr/holidays', [
            'holiday_date' => '2026-12-25',
            'name' => 'Hari Libur',
            'type' => 'national',
        ])->assertForbidden();
    }

    public function test_holiday_without_schedule_does_not_create_absence(): void
    {
        Holiday::create([
            'holiday_date' => '2026-12-25',
            'name' => 'Hari Libur Nasional',
            'type' => 'national',
            'is_active' => true,
        ]);

        $result = app(ProcessAttendanceForWorkDate::class)->execute(
            '2026-12-25',
            CarbonImmutable::parse('2026-12-25 23:59:00', 'Asia/Jakarta')
        );

        $this->assertTrue($result->isEmpty());
        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_scheduled_employee_on_holiday_is_still_processed(): void
    {
        Holiday::create([
            'holiday_date' => '2026-12-25',
            'name' => 'Hari Libur Nasional',
            'type' => 'national',
            'is_active' => true,
        ]);

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
            'work_date' => '2026-12-25',
            'status' => 'scheduled',
        ]);

        app(ProcessAttendanceForWorkDate::class)->execute(
            '2026-12-25',
            CarbonImmutable::parse('2026-12-25 16:00:00', 'Asia/Jakarta')
        );

        $this->assertDatabaseHas('attendances', [
            'shift_schedule_id' => $schedule->id,
            'attendance_status' => 'ABSENT',
            'state' => 'absent',
        ]);
    }

    public function test_active_holiday_is_visible_on_hr_attendance_monitor(): void
    {
        Holiday::create([
            'holiday_date' => '2026-12-25',
            'name' => 'Hari Libur Nasional',
            'type' => 'national',
            'reference' => 'Keputusan Pemerintah',
            'is_active' => true,
        ]);

        $hr = $this->user(UserRole::HrAdmin, 'hr@example.test');

        $this->actingAs($hr)
            ->get('/hr/attendance?work_date=2026-12-25')
            ->assertOk()
            ->assertSee('Hari Libur Nasional')
            ->assertSee('Keputusan Pemerintah')
            ->assertSee('Hari libur tanpa jadwal kerja aktif.');
    }

    private function user(UserRole $role, string $email): User
    {
        return User::create([
            'name' => $role->value,
            'email' => $email,
            'password' => 'Secret123!',
            'role' => $role,
            'is_active' => true,
        ]);
    }
}
