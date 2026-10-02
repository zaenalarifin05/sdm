<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Payroll;
use Illuminate\View\View;

class PayrollEntryController extends Controller
{
    public function show(Payroll $payroll): View
    {
        $payroll->load(['employee.department', 'period', 'items']);

        return view('finance.payroll.entry', compact('payroll'));
    }
}
