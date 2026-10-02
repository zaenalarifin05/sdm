<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OvertimeRequest extends Model
{
    protected $fillable = [
        'employee_id',
        'shift_schedule_id',
        'work_date',
        'start_at',
        'end_at',
        'duration_minutes',
        'reason',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'work_date' => 'date',
            'start_at' => 'immutable_datetime',
            'end_at' => 'immutable_datetime',
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

    public function approvals(): HasMany
    {
        return $this->hasMany(OvertimeApproval::class);
    }
}
