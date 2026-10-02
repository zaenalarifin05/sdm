<?php

namespace App\Services\Payroll;

use App\Models\Payroll;

class RecalculatePayrollTotals
{
    public function execute(Payroll $payroll): Payroll
    {
        $allowanceCents = $this->sumItemCents($payroll, 'ALLOWANCE');
        $deductionCents = $this->sumItemCents($payroll, 'DEDUCTION');
        $baseSalaryCents = $this->toCents((string) $payroll->base_salary_snapshot);

        $grossCents = $baseSalaryCents + $allowanceCents;
        $netCents = $grossCents - $deductionCents;

        $payroll->update([
            'total_allowance' => $this->fromCents($allowanceCents),
            'gross_pay' => $this->fromCents($grossCents),
            'total_deduction' => $this->fromCents($deductionCents),
            'net_pay' => $this->fromCents($netCents),
        ]);

        return $payroll->fresh(['items']);
    }

    private function sumItemCents(Payroll $payroll, string $type): int
    {
        return $payroll->items()
            ->where('type', $type)
            ->pluck('amount')
            ->reduce(
                fn (int $carry, $amount) => $carry + $this->toCents((string) $amount),
                0
            );
    }

    private function toCents(string $amount): int
    {
        $normalized = trim($amount);

        if (! str_contains($normalized, '.')) {
            return ((int) $normalized) * 100;
        }

        [$whole, $fraction] = explode('.', $normalized, 2);
        $fraction = substr(str_pad($fraction, 2, '0'), 0, 2);

        return ((int) $whole) * 100 + (int) $fraction;
    }

    private function fromCents(int $cents): string
    {
        $sign = $cents < 0 ? '-' : '';
        $absolute = abs($cents);

        return $sign.intdiv($absolute, 100).'.'.str_pad((string) ($absolute % 100), 2, '0', STR_PAD_LEFT);
    }
}
