<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Attendance;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Shift;
use App\Models\ShiftSchedule;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MyAttendanceHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_history_only_reads_linked_employee_attendance(): void
    {
        $department = Department::create(['code' => 'PRD', 'name' => 'Produksi', 'is_active' => true]);

        $user = User::create([
            'name' => 'Pegawai Satu',
            'email' => 'employee@example.test',
            'password' => 'Secret123!',
            'role' => UserRole::Employee,
            'is_active' => true,
        ]);

        $employee = Employee::create([
            'nip' => 'EMP001',
            'name' => 'Pegawai Satu',
            'department_id' => $department->id,
            'user_id' => $user->id,
            'employment_status' => 'active',
            'attendance_pin_hash' => Hash::make('123456'),
            'is_active' => true,
        ]);

        $other = Employee::create([
            'nip' => 'EMP002',
            'name' => 'Pegawai Dua',
            'department_id' => $department->id,
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

        $ownSchedule = ShiftSchedule::create([
            'employee_id' => $employee->id,
            'shift_id' => $shift->id,
            'work_date' => '2026-10-02',
            'status' => 'scheduled',
        ]);

        $otherSchedule = ShiftSchedule::create([
            'employee_id' => $other->id,
            'shift_id' => $shift->id,
            'work_date' => '2026-10-03',
            'status' => 'scheduled',
        ]);

        $this->attendance($employee, $ownSchedule, '2026-10-02');
        $this->attendance($other, $otherSchedule, '2026-10-03');

        $this->actingAs($user)
            ->get('/my/attendance')
            ->assertOk()
            ->assertSee('02-10-2026')
            ->assertDontSee('03-10-2026');
    }

    private function attendance(Employee $employee, ShiftSchedule $schedule, string $date): void
    {
        $start = CarbonImmutable::parse($date.' 07:00:00', 'Asia/Jakarta');

        Attendance::create([
            'employee_id' => $employee->id,
            'shift_schedule_id' => $schedule->id,
            'work_date' => $date,
            'scheduled_start_at' => $start,
            'scheduled_end_at' => $start->addHours(8),
            'check_in_at' => $start,
            'check_out_at' => $start->addHours(8),
            'state' => 'completed',
        ]);
    }
}
