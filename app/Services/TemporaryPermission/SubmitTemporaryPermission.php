<?php

namespace App\Services\TemporaryPermission;

use App\Models\Employee;
use App\Models\ShiftSchedule;
use App\Models\TemporaryPermission;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SubmitTemporaryPermission
{
    public function execute(Employee $employee, int $shiftScheduleId, string $reason): TemporaryPermission
    {
        $schedule = ShiftSchedule::query()
            ->with(['employee.department.manager.user', 'shift'])
            ->whereKey($shiftScheduleId)
            ->where('employee_id', $employee->id)
            ->where('status', 'scheduled')
            ->first();

        if (! $schedule) {
            throw ValidationException::withMessages([
                'shift_schedule_id' => 'Jadwal kerja tidak valid untuk pegawai ini.',
            ]);
        }

        if (! $schedule->employee->department?->manager
            || ! $schedule->employee->department->manager->user?->is_active) {
            throw ValidationException::withMessages([
                'shift_schedule_id' => 'Atasan departemen belum dikonfigurasi.',
            ]);
        }

        $active = TemporaryPermission::query()
            ->where('employee_id', $employee->id)
            ->where('shift_schedule_id', $schedule->id)
            ->whereIn('status', ['PENDING_MANAGER', 'APPROVED'])
            ->exists();

        if ($active) {
            throw ValidationException::withMessages([
                'shift_schedule_id' => 'Masih ada izin keluar aktif pada jadwal ini.',
            ]);
        }

        return DB::transaction(fn () => TemporaryPermission::create([
            'employee_id' => $employee->id,
            'shift_schedule_id' => $schedule->id,
            'reason' => trim($reason),
            'status' => 'PENDING_MANAGER',
        ]));
    }
}
