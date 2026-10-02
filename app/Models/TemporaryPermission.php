<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TemporaryPermission extends Model
{
    protected $fillable = [
        'employee_id',
        'shift_schedule_id',
        'reason',
        'status',
        'approved_by_user_id',
        'approval_note',
        'approved_at',
        'out_at',
        'returned_at',
        'duration_seconds',
    ];

    protected function casts(): array
    {
        return [
            'approved_at' => 'immutable_datetime',
            'out_at' => 'immutable_datetime',
            'returned_at' => 'immutable_datetime',
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

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    public function getDurationMinutesAttribute(): ?int
    {
        if ($this->duration_seconds === null) {
            return null;
        }

        return (int) ceil($this->duration_seconds / 60);
    }
}
