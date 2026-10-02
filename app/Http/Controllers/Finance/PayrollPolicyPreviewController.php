<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\PayrollPeriod;
use App\Services\Payroll\ApplyPayrollPoliciesToPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PayrollPolicyPreviewController extends Controller
{
    public function store(
        Request $request,
        PayrollPeriod $payrollPeriod,
        ApplyPayrollPoliciesToPeriod $applyPolicies
    ): RedirectResponse {
        $payrolls = $applyPolicies->execute($payrollPeriod, $request->user());

        return redirect()->route('finance.payroll.show', $payrollPeriod)
            ->with('status', 'Policy payroll diterapkan ulang pada '.$payrolls->count().' draft pegawai.');
    }
}
