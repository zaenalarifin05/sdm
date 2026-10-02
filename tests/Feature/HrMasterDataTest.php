<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class HrMasterDataTest extends TestCase
{
    use RefreshDatabase;

    private function hr(): User
    {
        return User::create([
            'name' => 'HR Admin',
            'email' => 'hr@example.test',
            'password' => 'Secret123!',
            'role' => UserRole::HrAdmin,
            'is_active' => true,
        ]);
    }

    public function test_hr_can_create_employee_with_hashed_attendance_pin(): void
    {
        $department = Department::create(['code' => 'PRD', 'name' => 'Produksi', 'is_active' => true]);

        $this->actingAs($this->hr())->post('/hr/employees', [
            'nip' => 'EMP001',
            'name' => 'Pegawai Satu',
            'department_id' => $department->id,
            'join_date' => '2026-10-02',
            'employment_status' => 'active',
            'pin' => '123456',
        ])->assertRedirect(route('hr.employees.index'));

        $employee = Employee::where('nip', 'EMP001')->firstOrFail();

        $this->assertTrue(Hash::check('123456', $employee->attendance_pin_hash));
        $this->assertNotSame('123456', $employee->attendance_pin_hash);
    }

    public function test_hr_can_assign_one_shift_per_employee_per_work_date(): void
    {
        $department = Department::create(['code' => 'PRD', 'name' => 'Produksi', 'is_active' => true]);
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

        $hr = $this->hr();

        $this->actingAs($hr)->post('/hr/schedules', [
            'employee_id' => $employee->id,
            'shift_id' => $shift->id,
            'work_date' => '2026-10-03',
        ])->assertRedirect(route('hr.schedules.index'));

        $this->actingAs($hr)->post('/hr/schedules', [
            'employee_id' => $employee->id,
            'shift_id' => $shift->id,
            'work_date' => '2026-10-03',
        ])->assertSessionHasErrors('work_date');

        $this->assertDatabaseCount('shift_schedules', 1);
    }
}
