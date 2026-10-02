<?php

namespace App\Services\Payroll;

use App\Models\Payroll;

class RecalculatePayrollTotals
{
    public function __construct(
        private readonly PayrollMoney $money,
    ) {
    }

    public function execute(Payroll $payroll): Payroll
    {
        $allowanceCents = $this->sumItemCents($payroll, 'ALLOWANCE');
        $deductionCents = $this->sumItemCents($payroll, 'DEDUCTION');
        $baseSalaryCents = $this->money->toCents((string) $payroll->base_salary_snapshot);

        $grossCents = $baseSalaryCents + $allowanceCents;
        $netCents = $grossCents - $deductionCents;

        $payroll->update([
            'total_allowance' => $this->money->fromCents($allowanceCents),
            'gross_pay' => $this->money->fromCents($grossCents),
            'total_deduction' => $this->money->fromCents($deductionCents),
            'net_pay' => $this->money->fromCents($netCents),
        ]);

        return $payroll->fresh(['items']);
    }

    private function sumItemCents(Payroll $payroll, string $type): int
    {
        return $payroll->items()
            ->where('type', $type)
            ->pluck('amount')
            ->reduce(
                fn (int $carry, $amount) => $carry + $this->money->toCents((string) $amount),
                0
            );
    }
}
