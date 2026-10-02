<?php

namespace App\Services\Overtime;

use App\Models\Employee;
use App\Models\OvertimeRequest;
use App\Models\ShiftSchedule;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SubmitOvertimeRequest
{
    public function execute(
        Employee $employee,
        int $shiftScheduleId,
        string $startAt,
        string $endAt,
        string $reason,
    ): OvertimeRequest {
        $timezone = (string) config('app.timezone', 'Asia/Jakarta');
        $start = CarbonImmutable::parse($startAt, $timezone);
        $end = CarbonImmutable::parse($endAt, $timezone);

        if ($end->lessThanOrEqualTo($start)) {
            throw ValidationException::withMessages([
                'end_at' => 'Waktu selesai lembur harus setelah waktu mulai.',
            ]);
        }

        $durationMinutes = intdiv($end->getTimestamp() - $start->getTimestamp(), 60);

        if ($durationMinutes < 1 || $durationMinutes > 1440) {
            throw ValidationException::withMessages([
                'end_at' => 'Durasi lembur harus lebih dari 0 dan tidak lebih dari 24 jam.',
            ]);
        }

        $schedule = ShiftSchedule::query()
            ->with(['shift', 'attendance'])
            ->whereKey($shiftScheduleId)
            ->where('employee_id', $employee->id)
            ->where('status', 'scheduled')
            ->first();

        if (! $schedule) {
            throw ValidationException::withMessages([
                'shift_schedule_id' => 'Jadwal kerja tidak valid untuk pegawai ini.',
            ]);
        }

        $employee->loadMissing(['department.manager.user']);

        if (! $employee->department?->manager || ! $employee->department->manager->user?->is_active) {
            throw ValidationException::withMessages([
                'shift_schedule_id' => 'Atasan departemen belum dikonfigurasi.',
            ]);
        }

        $overlap = OvertimeRequest::query()
            ->where('employee_id', $employee->id)
            ->whereIn('status', ['PENDING_MANAGER', 'PENDING_HR', 'APPROVED'])
            ->where('start_at', '<', $end)
            ->where('end_at', '>', $start)
            ->exists();

        if ($overlap) {
            throw ValidationException::withMessages([
                'start_at' => 'Sudah ada pengajuan lembur aktif yang waktunya bertumpang tindih.',
            ]);
        }

        return DB::transaction(fn () => OvertimeRequest::create([
            'employee_id' => $employee->id,
            'shift_schedule_id' => $schedule->id,
            'work_date' => $schedule->work_date,
            'start_at' => $start,
            'end_at' => $end,
            'duration_minutes' => $durationMinutes,
            'reason' => trim($reason),
            'status' => 'PENDING_MANAGER',
        ]));
    }
}
