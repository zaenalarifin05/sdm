<?php

namespace App\Services\Payroll;

use App\Models\PayrollPeriod;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApplyPayrollPoliciesToPeriod
{
    public function __construct(
        private readonly ApplyPayrollPolicies $applyPolicies,
    ) {
    }

    public function execute(PayrollPeriod $period, User $actor): Collection
    {
        if (! $actor->hasAnyRole('finance')) {
            abort(403);
        }

        if ($period->status !== 'DRAFT') {
            throw ValidationException::withMessages([
                'payroll_period' => 'Policy hanya dapat diterapkan pada payroll period DRAFT.',
            ]);
        }

        return DB::transaction(function () use ($period): Collection {
            return $period->payrolls()
                ->lockForUpdate()
                ->get()
                ->map(fn ($payroll) => $this->applyPolicies->execute($payroll));
        });
    }
}
