<?php

namespace App\Services\Payroll;

use App\Models\PayrollPeriod;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FinalizePayrollPeriod
{
    public function __construct(
        private readonly ApplyPayrollPolicies $applyPolicies,
        private readonly PayrollPolicyResolver $resolver,
    ) {
    }

    public function execute(PayrollPeriod $period, User $actor): PayrollPeriod
    {
        if (! $actor->hasAnyRole('finance')) {
            abort(403);
        }

        return DB::transaction(function () use ($period, $actor): PayrollPeriod {
            $lockedPeriod = PayrollPeriod::query()
                ->whereKey($period->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedPeriod->status !== 'DRAFT') {
                throw ValidationException::withMessages([
                    'payroll_period' => 'Payroll period tidak lagi berstatus DRAFT.',
                ]);
            }

            $payrolls = $lockedPeriod->payrolls()
                ->lockForUpdate()
                ->get();

            if ($payrolls->isEmpty()) {
                throw ValidationException::withMessages([
                    'payroll_period' => 'Payroll draft belum digenerate.',
                ]);
            }

            if ($payrolls->contains(fn ($payroll) => $payroll->incomplete_days > 0)) {
                throw ValidationException::withMessages([
                    'payroll_period' => 'Masih ada attendance INCOMPLETE. Selesaikan attendance sebelum finalisasi payroll.',
                ]);
            }

            $policies = $this->resolver->forPeriod($lockedPeriod);

            if (! $policies->get('absent_deduction')?->enabled
                && $payrolls->contains(fn ($payroll) => $payroll->absent_days > 0)) {
                throw ValidationException::withMessages([
                    'payroll_period' => 'Policy potongan ABSENT belum dikonfigurasi untuk periode ini.',
                ]);
            }

            if (! $policies->get('late_deduction')?->enabled
                && $payrolls->contains(fn ($payroll) => $payroll->late_days > 0)) {
                throw ValidationException::withMessages([
                    'payroll_period' => 'Policy potongan keterlambatan belum dikonfigurasi untuk periode ini.',
                ]);
            }

            if (! $policies->get('overtime_pay')?->enabled
                && $payrolls->contains(fn ($payroll) => $payroll->approved_overtime_minutes > 0)) {
                throw ValidationException::withMessages([
                    'payroll_period' => 'Policy nilai lembur belum dikonfigurasi untuk periode ini.',
                ]);
            }

            foreach ($payrolls as $payroll) {
                $payroll = $this->applyPolicies->execute($payroll);

                $payroll->update([
                    'status' => 'FINALIZED',
                    'finalized_at' => now(),
                    'finalized_by' => $actor->id,
                ]);
            }

            $lockedPeriod->update(['status' => 'FINALIZED']);

            return $lockedPeriod->fresh('payrolls');
        });
    }
}
