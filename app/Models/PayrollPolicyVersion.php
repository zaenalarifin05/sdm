<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollPolicyVersion extends Model
{
    protected $fillable = [
        'policy_key',
        'enabled',
        'mode',
        'value',
        'effective_from',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'effective_from' => 'date',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
