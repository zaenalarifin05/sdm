<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PortalManagerSetupTest extends TestCase
{
    use RefreshDatabase;

    public function test_hr_can_provision_employee_portal_account(): void
    {
        $department = Department::create([
            'code' => 'PRD',
            'name' => 'Produksi',
            'is_active' => true,
        ]);

        $hr = User::create([
            'name' => 'HR Admin',
            'email' => 'hr@example.test',
            'password' => 'Secret123!',
            'role' => UserRole::HrAdmin,
            'is_active' => true,
        ]);

        $this->actingAs($hr)->post('/hr/employees', [
            'nip' => 'EMP001',
            'name' => 'Pegawai Satu',
            'department_id' => $department->id,
            'join_date' => '2026-10-02',
            'employment_status' => 'active',
            'pin' => '123456',
            'portal_email' => 'employee@example.test',
            'portal_password' => 'VerySecret123!',
        ])->assertRedirect(route('hr.employees.index'));

        $employee = Employee::where('nip', 'EMP001')->with('user')->firstOrFail();

        $this->assertNotNull($employee->user);
        $this->assertSame('employee@example.test', $employee->user->email);
        $this->assertSame(UserRole::Employee, $employee->user->role);
        $this->assertTrue(Hash::check('VerySecret123!', $employee->user->password));
    }

    public function test_hr_can_assign_department_manager_and_role_is_derived(): void
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
            'role' => UserRole::Employee,
            'is_active' => true,
        ]);

        $managerEmployee = Employee::create([
            'nip' => 'MGR001',
            'name' => 'Supervisor',
            'department_id' => $department->id,
            'user_id' => $managerUser->id,
            'employment_status' => 'active',
            'attendance_pin_hash' => Hash::make('123456'),
            'is_active' => true,
        ]);

        $hr = User::create([
            'name' => 'HR Admin',
            'email' => 'hr@example.test',
            'password' => 'Secret123!',
            'role' => UserRole::HrAdmin,
            'is_active' => true,
        ]);

        $this->actingAs($hr)->put('/hr/departments/'.$department->id, [
            'code' => 'PRD',
            'name' => 'Produksi',
            'manager_employee_id' => $managerEmployee->id,
            'is_active' => '1',
        ])->assertRedirect(route('hr.departments.index'));

        $this->assertDatabaseHas('departments', [
            'id' => $department->id,
            'manager_employee_id' => $managerEmployee->id,
        ]);

        $this->assertSame(
            UserRole::Manager,
            $managerUser->fresh()->role
        );
    }
}
