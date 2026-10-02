<?php

namespace App\Services\Payroll;

use App\Models\PayrollPeriod;
use App\Models\PayrollPolicyVersion;
use Illuminate\Support\Collection;

class PayrollPolicyResolver
{
    public const KEYS = [
        'absent_deduction',
        'late_deduction',
        'overtime_pay',
    ];

    public function forPeriod(PayrollPeriod $period): Collection
    {
        return collect(self::KEYS)->mapWithKeys(function (string $key) use ($period): array {
            $version = PayrollPolicyVersion::query()
                ->where('policy_key', $key)
                ->whereDate('effective_from', '<=', $period->period_end->format('Y-m-d'))
                ->orderByDesc('effective_from')
                ->orderByDesc('id')
                ->first();

            return [$key => $version];
        });
    }

    public function oneForPeriod(PayrollPeriod $period, string $key): ?PayrollPolicyVersion
    {
        return $this->forPeriod($period)->get($key);
    }
}
