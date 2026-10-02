<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    protected $fillable = [
        'employee_id',
        'shift_schedule_id',
        'work_date',
        'scheduled_start_at',
        'scheduled_end_at',
        'check_in_at',
        'check_out_at',
        'state',
        'attendance_status',
        'late_minutes',
        'early_leave_minutes',
    ];

    protected function casts(): array
    {
        return [
            'work_date' => 'date',
            'scheduled_start_at' => 'immutable_datetime',
            'scheduled_end_at' => 'immutable_datetime',
            'check_in_at' => 'immutable_datetime',
            'check_out_at' => 'immutable_datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function shiftSchedule(): BelongsTo
    {
        return $this->belongsTo(ShiftSchedule::class);
    }
}
