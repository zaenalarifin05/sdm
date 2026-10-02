<?php

namespace App\Services\TemporaryPermission;

use App\Models\Attendance;
use App\Models\TemporaryPermission;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecordTemporaryPermissionMovement
{
    public function start(User $actor, TemporaryPermission $permission, ?CarbonImmutable $now = null): TemporaryPermission
    {
        $this->assertOwner($actor, $permission);

        if ($permission->status !== 'APPROVED' || $permission->out_at || $permission->returned_at) {
            throw ValidationException::withMessages([
                'temporary_permission' => 'Izin keluar belum dapat dimulai.',
            ]);
        }

        $attendance = Attendance::query()
            ->where('shift_schedule_id', $permission->shift_schedule_id)
            ->where('employee_id', $permission->employee_id)
            ->whereNotNull('check_in_at')
            ->whereNull('check_out_at')
            ->first();

        if (! $attendance) {
            throw ValidationException::withMessages([
                'temporary_permission' => 'Pegawai harus sudah Check-In dan belum Check-Out.',
            ]);
        }

        $timezone = (string) config('app.timezone', 'Asia/Jakarta');
        $now = ($now ?? CarbonImmutable::now($timezone))->setTimezone($timezone);

        return DB::transaction(function () use ($permission, $now): TemporaryPermission {
            $permission->update(['out_at' => $now]);

            return $permission->fresh();
        });
    }

    public function return(User $actor, TemporaryPermission $permission, ?CarbonImmutable $now = null): TemporaryPermission
    {
        $this->assertOwner($actor, $permission);

        if ($permission->status !== 'APPROVED' || ! $permission->out_at || $permission->returned_at) {
            throw ValidationException::withMessages([
                'temporary_permission' => 'Izin keluar belum dapat diselesaikan.',
            ]);
        }

        $timezone = (string) config('app.timezone', 'Asia/Jakarta');
        $now = ($now ?? CarbonImmutable::now($timezone))->setTimezone($timezone);

        if ($now->lessThanOrEqualTo($permission->out_at)) {
            throw ValidationException::withMessages([
                'temporary_permission' => 'Waktu kembali harus setelah waktu keluar.',
            ]);
        }

        $durationSeconds = $now->getTimestamp() - $permission->out_at->getTimestamp();

        return DB::transaction(function () use ($permission, $now, $durationSeconds): TemporaryPermission {
            $permission->update([
                'returned_at' => $now,
                'duration_seconds' => $durationSeconds,
                'status' => 'COMPLETED',
            ]);

            return $permission->fresh();
        });
    }

    private function assertOwner(User $actor, TemporaryPermission $permission): void
    {
        $actor->loadMissing('employee');

        if (! $actor->employee || $permission->employee_id !== $actor->employee->id) {
            abort(403);
        }
    }
}
