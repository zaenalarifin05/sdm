<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveApproval extends Model
{
    protected $fillable = [
        'leave_request_id', 'stage', 'action', 'approver_user_id', 'note', 'acted_at',
    ];

    protected function casts(): array
    {
        return ['acted_at' => 'immutable_datetime'];
    }

    public function leaveRequest(): BelongsTo
    {
        return $this->belongsTo(LeaveRequest::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_user_id');
    }
}
