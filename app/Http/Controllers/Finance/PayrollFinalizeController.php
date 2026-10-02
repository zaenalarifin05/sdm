<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\PayrollPeriod;
use App\Services\Payroll\FinalizePayrollPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PayrollFinalizeController extends Controller
{
    public function store(
        Request $request,
        PayrollPeriod $payrollPeriod,
        FinalizePayrollPeriod $finalize
    ): RedirectResponse {
        $finalize->execute($payrollPeriod, $request->user());

        return redirect()->route('finance.payroll.show', $payrollPeriod)
            ->with('status', 'Payroll period berhasil difinalisasi.');
    }
}
