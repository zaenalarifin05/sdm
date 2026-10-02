<?php

namespace App\Services\Attendance;

use App\Models\Attendance;

class AttendanceStatusPolicy
{
    public function lateMinutes(Attendance $attendance): int
    {
        if (! $attendance->check_in_at) {
            return 0;
        }

        $secondsLate = $attendance->check_in_at->getTimestamp()
            - $attendance->scheduled_start_at->getTimestamp();

        return $secondsLate > 0 ? intdiv($secondsLate, 60) : 0;
    }

    public function completedStatus(Attendance $attendance): string
    {
        $tolerance = (int) config('attendance.late_tolerance_minutes', 15);

        return $this->lateMinutes($attendance) > $tolerance
            ? 'LATE'
            : 'PRESENT';
    }
}
