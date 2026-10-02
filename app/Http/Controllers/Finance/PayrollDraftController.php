<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\PayrollPeriod;
use App\Services\Payroll\GeneratePayrollDraft;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PayrollDraftController extends Controller
{
    public function generate(
        Request $request,
        PayrollPeriod $payrollPeriod,
        GeneratePayrollDraft $generator
    ): RedirectResponse {
        $generated = $generator->execute($payrollPeriod, $request->user());

        return redirect()->route('finance.payroll.show', $payrollPeriod)
            ->with('status', 'Payroll draft berhasil digenerate untuk '.$generated->count().' pegawai.');
    }
}
