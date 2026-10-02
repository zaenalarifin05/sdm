<?php

namespace App\Data;

use App\Models\Attendance;

readonly class AttendancePunchResult
{
    public function __construct(
        public string $action,
        public Attendance $attendance,
    ) {
    }
}
