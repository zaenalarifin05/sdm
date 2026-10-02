<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeCompensation extends Model
{
    protected $fillable = [
        'employee_id', 'effective_from', 'base_salary', 'currency', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'effective_from' => 'date',
            'base_salary' => 'decimal:2',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
