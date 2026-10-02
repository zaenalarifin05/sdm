<?php

namespace App\Services\Payroll;

use App\Models\Payroll;
use App\Models\PayrollItem;
use App\Models\PayrollPolicyVersion;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApplyPayrollPolicies
{
    public function __construct(
        private readonly PayrollMoney $money,
        private readonly RecalculatePayrollTotals $recalculate,
        private readonly PayrollPolicyResolver $resolver,
    ) {
    }

    public function execute(Payroll $payroll): Payroll
    {
        if ($payroll->status !== 'DRAFT') {
            throw ValidationException::withMessages([
                'payroll' => 'Policy hanya dapat diterapkan pada payroll DRAFT.',
            ]);
        }

        $payroll->loadMissing('period');
        $policies = $this->resolver->forPeriod($payroll->period);

        return DB::transaction(function () use ($payroll, $policies): Payroll {
            $payroll->items()
                ->where('source_type', 'POLICY')
                ->delete();

            $this->applyAbsentPolicy($payroll, $policies->get('absent_deduction'));
            $this->applyLatePolicy($payroll, $policies->get('late_deduction'));
            $this->applyOvertimePolicy($payroll, $policies->get('overtime_pay'));

            return $this->recalculate->execute($payroll);
        });
    }

    private function applyAbsentPolicy(Payroll $payroll, ?PayrollPolicyVersion $policy): void
    {
        if ($payroll->absent_days < 1 || ! $policy?->enabled) {
            return;
        }

        $amountCents = match ($policy->mode) {
            'fixed_per_day' => $this->positiveMoney($policy->value) * $payroll->absent_days,
            'salary_divisor_per_day' => $this->salaryDivisorDeduction($payroll, $policy->value),
            default => $this->invalidPolicy('ABSENT'),
        };

        $this->createItem(
            $payroll,
            $policy,
            'DEDUCTION',
            'ABSENT_DEDUCTION',
            'Potongan Tidak Masuk',
            (string) $payroll->absent_days,
            $amountCents
        );
    }

    private function applyLatePolicy(Payroll $payroll, ?PayrollPolicyVersion $policy): void
    {
        if ($payroll->late_days < 1 || ! $policy?->enabled) {
            return;
        }

        [$quantity, $amountCents] = match ($policy->mode) {
            'fixed_per_minute' => [
                (string) $payroll->late_minutes,
                $this->positiveMoney($policy->value) * $payroll->late_minutes,
            ],
            'fixed_per_incident' => [
                (string) $payroll->late_days,
                $this->positiveMoney($policy->value) * $payroll->late_days,
            ],
            default => $this->invalidPolicy('LATE'),
        };

        $this->createItem(
            $payroll,
            $policy,
            'DEDUCTION',
            'LATE_DEDUCTION',
            'Potongan Keterlambatan',
            $quantity,
            $amountCents
        );
    }

    private function applyOvertimePolicy(Payroll $payroll, ?PayrollPolicyVersion $policy): void
    {
        if ($payroll->approved_overtime_minutes < 1 || ! $policy?->enabled) {
            return;
        }

        $amountCents = match ($policy->mode) {
            'fixed_per_minute' => $this->positiveMoney($policy->value) * $payroll->approved_overtime_minutes,
            'fixed_per_hour' => $this->roundHalfUp(
                $this->positiveMoney($policy->value) * $payroll->approved_overtime_minutes,
                60
            ),
            default => $this->invalidPolicy('OVERTIME'),
        };

        $this->createItem(
            $payroll,
            $policy,
            'ALLOWANCE',
            'OVERTIME_PAY',
            'Upah Lembur',
            (string) $payroll->approved_overtime_minutes,
            $amountCents
        );
    }

    private function salaryDivisorDeduction(Payroll $payroll, mixed $value): int
    {
        if (! is_numeric($value) || (int) $value < 1) {
            return $this->invalidPolicy('ABSENT');
        }

        $baseSalaryCents = $this->money->toCents((string) $payroll->base_salary_snapshot);

        return $this->roundHalfUp(
            $baseSalaryCents * $payroll->absent_days,
            (int) $value
        );
    }

    private function positiveMoney(mixed $value): int
    {
        if ($value === null) {
            return $this->invalidPolicy('PAYROLL');
        }

        $cents = $this->money->toCents((string) $value);

        if ($cents < 1) {
            return $this->invalidPolicy('PAYROLL');
        }

        return $cents;
    }

    private function roundHalfUp(int $numerator, int $denominator): int
    {
        return intdiv(($numerator * 2) + $denominator, $denominator * 2);
    }

    private function createItem(
        Payroll $payroll,
        PayrollPolicyVersion $policy,
        string $type,
        string $code,
        string $name,
        string $quantity,
        int $amountCents
    ): void {
        PayrollItem::create([
            'payroll_id' => $payroll->id,
            'type' => $type,
            'code' => $code,
            'name' => $name,
            'quantity' => $quantity,
            'amount' => $this->money->fromCents($amountCents),
            'source_type' => 'POLICY',
            'source_id' => $policy->id,
        ]);
    }

    private function invalidPolicy(string $policy): never
    {
        throw ValidationException::withMessages([
            'payroll_policy' => "Konfigurasi policy {$policy} belum valid.",
        ]);
    }
}
