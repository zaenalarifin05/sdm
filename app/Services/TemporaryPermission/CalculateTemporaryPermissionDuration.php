<?php

namespace App\Services\TemporaryPermission;

use App\Models\ShiftSchedule;
use App\Models\TemporaryPermission;
use Carbon\CarbonImmutable;

class CalculateTemporaryPermissionDuration
{
    public function secondsForSchedule(
        ShiftSchedule $schedule,
        CarbonImmutable $asOf,
        CarbonImmutable $scheduledEnd
    ): int {
        $permissions = TemporaryPermission::query()
            ->where('shift_schedule_id', $schedule->id)
            ->whereIn('status', ['APPROVED', 'COMPLETED'])
            ->get();

        $seconds = 0;
        $effectiveEnd = $asOf->lessThan($scheduledEnd) ? $asOf : $scheduledEnd;

        foreach ($permissions as $permission) {
            if ($permission->duration_seconds !== null) {
                $seconds += $permission->duration_seconds;
                continue;
            }

            if ($permission->out_at && ! $permission->returned_at
                && $effectiveEnd->greaterThan($permission->out_at)) {
                $seconds += $effectiveEnd->getTimestamp() - $permission->out_at->getTimestamp();
            }
        }

        return $seconds;
    }

    public function hasOpenPermission(ShiftSchedule $schedule): bool
    {
        return TemporaryPermission::query()
            ->where('shift_schedule_id', $schedule->id)
            ->where('status', 'APPROVED')
            ->whereNotNull('out_at')
            ->whereNull('returned_at')
            ->exists();
    }
}
