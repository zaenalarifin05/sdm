<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payroll extends Model
{
    protected $fillable = [
        'payroll_period_id', 'employee_id', 'employee_compensation_id',
        'base_salary_snapshot', 'currency',
        'scheduled_days', 'present_days', 'late_days', 'late_minutes',
        'absent_days', 'leave_days', 'permit_days', 'incomplete_days',
        'approved_overtime_minutes',
        'total_allowance', 'gross_pay', 'total_deduction', 'net_pay',
        'status', 'generated_at', 'generated_by', 'finalized_at', 'finalized_by',
    ];

    protected function casts(): array
    {
        return [
            'base_salary_snapshot' => 'decimal:2',
            'total_allowance' => 'decimal:2',
            'gross_pay' => 'decimal:2',
            'total_deduction' => 'decimal:2',
            'net_pay' => 'decimal:2',
            'generated_at' => 'immutable_datetime',
            'finalized_at' => 'immutable_datetime',
        ];
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class, 'payroll_period_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function compensation(): BelongsTo
    {
        return $this->belongsTo(EmployeeCompensation::class, 'employee_compensation_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PayrollItem::class);
    }

    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function finalizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finalized_by');
    }
}
