<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Attendance;
use App\Models\Department;
use App\Models\Employee;
use App\Models\OvertimeRequest;
use App\Models\Shift;
use App\Models\ShiftSchedule;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class OvertimeApprovalFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_can_submit_overtime_and_duration_is_calculated_server_side(): void
    {
        [$employeeUser, $employee, , , $shift] = $this->workforce();
        $schedule = $this->schedule($employee, $shift, '2026-10-10');

        $this->actingAs($employeeUser)->post('/my/overtime', [
            'shift_schedule_id' => $schedule->id,
            'start_at' => '2026-10-10T15:00',
            'end_at' => '2026-10-10T17:30',
            'reason' => 'Target produksi',
        ])->assertRedirect(route('my.overtime.index'));

        $overtime = OvertimeRequest::firstOrFail();

        $this->assertSame('PENDING_MANAGER', $overtime->status);
        $this->assertSame(150, $overtime->duration_minutes);
        $this->assertSame($schedule->id, $overtime->shift_schedule_id);
        $this->assertSame('2026-10-10', $overtime->work_date->format('Y-m-d'));
    }

    public function test_overtime_requires_manager_then_hr_final_approval(): void
    {
        [$employeeUser, $employee, $managerUser, $hr, $shift] = $this->workforce();
        $schedule = $this->schedule($employee, $shift, '2026-10-10');

        Attendance::create([
            'employee_id' => $employee->id,
            'shift_schedule_id' => $schedule->id,
            'work_date' => '2026-10-10',
            'scheduled_start_at' => CarbonImmutable::parse('2026-10-10 07:00', 'Asia/Jakarta'),
            'scheduled_end_at' => CarbonImmutable::parse('2026-10-10 15:00', 'Asia/Jakarta'),
            'check_in_at' => CarbonImmutable::parse('2026-10-10 07:00', 'Asia/Jakarta'),
            'check_out_at' => CarbonImmutable::parse('2026-10-10 17:00', 'Asia/Jakarta'),
            'state' => 'completed',
            'attendance_status' => 'PRESENT',
            'late_minutes' => 0,
        ]);

        $this->actingAs($employeeUser)->post('/my/overtime', [
            'shift_schedule_id' => $schedule->id,
            'start_at' => '2026-10-10T15:00',
            'end_at' => '2026-10-10T17:00',
            'reason' => 'Penyelesaian produksi',
        ]);

        $overtime = OvertimeRequest::firstOrFail();

        $this->actingAs($managerUser)
            ->post('/manager/overtime/'.$overtime->id.'/approve', ['note' => 'Disetujui atasan'])
            ->assertRedirect();

        $this->assertSame('PENDING_HR', $overtime->fresh()->status);

        $this->actingAs($hr)
            ->post('/hr/overtime/'.$overtime->id.'/approve', ['note' => 'Final HR'])
            ->assertRedirect();

        $this->assertSame('APPROVED', $overtime->fresh()->status);

        $this->assertDatabaseHas('overtime_approvals', [
            'overtime_request_id' => $overtime->id,
            'stage' => 'manager',
            'action' => 'approved',
        ]);

        $this->assertDatabaseHas('overtime_approvals', [
            'overtime_request_id' => $overtime->id,
            'stage' => 'hr',
            'action' => 'approved',
        ]);

        $this->actingAs($employeeUser)
            ->get('/my/overtime')
            ->assertOk()
            ->assertSee('PRESENT')
            ->assertSee('120 menit');
    }

    public function test_manager_cannot_approve_overtime_from_other_department(): void
    {
        [$employeeUser, $employee, , , $shift] = $this->workforce();
        $schedule = $this->schedule($employee, $shift, '2026-10-10');

        $this->actingAs($employeeUser)->post('/my/overtime', [
            'shift_schedule_id' => $schedule->id,
            'start_at' => '2026-10-10T15:00',
            'end_at' => '2026-10-10T16:00',
            'reason' => 'Lembur',
        ]);

        $overtime = OvertimeRequest::firstOrFail();

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
            ->post('/manager/overtime/'.$overtime->id.'/approve')
            ->assertForbidden();

        $this->assertSame('PENDING_MANAGER', $overtime->fresh()->status);
    }

    public function test_overlapping_active_overtime_request_is_rejected(): void
    {
        [$employeeUser, $employee, , , $shift] = $this->workforce();
        $schedule = $this->schedule($employee, $shift, '2026-10-10');

        $payload = [
            'shift_schedule_id' => $schedule->id,
            'start_at' => '2026-10-10T15:00',
            'end_at' => '2026-10-10T17:00',
            'reason' => 'Lembur pertama',
        ];

        $this->actingAs($employeeUser)->post('/my/overtime', $payload)
            ->assertRedirect(route('my.overtime.index'));

        $this->actingAs($employeeUser)->post('/my/overtime', [
            'shift_schedule_id' => $schedule->id,
            'start_at' => '2026-10-10T16:30',
            'end_at' => '2026-10-10T18:00',
            'reason' => 'Lembur kedua',
        ])->assertSessionHasErrors('start_at');

        $this->assertDatabaseCount('overtime_requests', 1);
    }

    public function test_employee_cannot_submit_overtime_for_another_employees_schedule(): void
    {
        [$employeeUser, $employee, , , $shift] = $this->workforce();

        $otherDepartment = Department::create([
            'code' => 'QAC',
            'name' => 'Quality',
            'is_active' => true,
        ]);

        $otherEmployee = Employee::create([
            'nip' => 'EMP002',
            'name' => 'Pegawai Dua',
            'department_id' => $otherDepartment->id,
            'employment_status' => 'active',
            'attendance_pin_hash' => Hash::make('654321'),
            'is_active' => true,
        ]);

        $otherSchedule = $this->schedule($otherEmployee, $shift, '2026-10-10');

        $this->actingAs($employeeUser)->post('/my/overtime', [
            'shift_schedule_id' => $otherSchedule->id,
            'start_at' => '2026-10-10T15:00',
            'end_at' => '2026-10-10T16:00',
            'reason' => 'Tidak sah',
        ])->assertSessionHasErrors('shift_schedule_id');

        $this->assertDatabaseCount('overtime_requests', 0);
    }

    public function test_rejected_manager_overtime_cannot_reach_hr_approval(): void
    {
        [$employeeUser, $employee, $managerUser, $hr, $shift] = $this->workforce();
        $schedule = $this->schedule($employee, $shift, '2026-10-10');

        $this->actingAs($employeeUser)->post('/my/overtime', [
            'shift_schedule_id' => $schedule->id,
            'start_at' => '2026-10-10T15:00',
            'end_at' => '2026-10-10T16:00',
            'reason' => 'Lembur',
        ]);

        $overtime = OvertimeRequest::firstOrFail();

        $this->actingAs($managerUser)
            ->post('/manager/overtime/'.$overtime->id.'/reject', ['note' => 'Tidak diperlukan'])
            ->assertRedirect();

        $this->assertSame('REJECTED_MANAGER', $overtime->fresh()->status);

        $this->actingAs($hr)
            ->post('/hr/overtime/'.$overtime->id.'/approve')
            ->assertSessionHasErrors('overtime_request');

        $this->assertSame('REJECTED_MANAGER', $overtime->fresh()->status);
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
