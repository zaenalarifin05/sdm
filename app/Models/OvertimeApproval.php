<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OvertimeApproval extends Model
{
    protected $fillable = [
        'overtime_request_id',
        'stage',
        'action',
        'approver_user_id',
        'note',
        'acted_at',
    ];

    protected function casts(): array
    {
        return ['acted_at' => 'immutable_datetime'];
    }

    public function overtimeRequest(): BelongsTo
    {
        return $this->belongsTo(OvertimeRequest::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_user_id');
    }
}
