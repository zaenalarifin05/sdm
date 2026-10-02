<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Payroll;
use Illuminate\View\View;

class PayslipController extends Controller
{
    public function show(Payroll $payroll): View
    {
        abort_unless($payroll->status === 'FINALIZED', 404);

        $payroll->load([
            'employee.department',
            'period',
            'items',
            'finalizer',
        ]);

        return view('payslips.show', [
            'payroll' => $payroll,
            'backUrl' => route('finance.payroll.show', $payroll->period),
        ]);
    }
}
