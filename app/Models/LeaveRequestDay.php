<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveRequestDay extends Model
{
    protected $fillable = ['leave_request_id', 'shift_schedule_id', 'work_date'];

    protected function casts(): array
    {
        return ['work_date' => 'date'];
    }

    public function leaveRequest(): BelongsTo
    {
        return $this->belongsTo(LeaveRequest::class);
    }

    public function shiftSchedule(): BelongsTo
    {
        return $this->belongsTo(ShiftSchedule::class);
    }
}
