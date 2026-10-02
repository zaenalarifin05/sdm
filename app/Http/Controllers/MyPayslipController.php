<?php

namespace App\Http\Controllers;

use App\Models\Payroll;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MyPayslipController extends Controller
{
    public function index(Request $request): View
    {
        $employee = $request->user()->employee;
        abort_unless($employee, 404);

        return view('payslips.my-index', [
            'payrolls' => Payroll::query()
                ->with('period')
                ->where('employee_id', $employee->id)
                ->where('status', 'FINALIZED')
                ->orderByDesc('finalized_at')
                ->paginate(20),
        ]);
    }

    public function show(Request $request, Payroll $payroll): View
    {
        $employee = $request->user()->employee;

        abort_unless(
            $employee
            && $payroll->employee_id === $employee->id
            && $payroll->status === 'FINALIZED',
            404
        );

        $payroll->load([
            'employee.department',
            'period',
            'items',
            'finalizer',
        ]);

        return view('payslips.show', [
            'payroll' => $payroll,
            'backUrl' => route('my.payslips.index'),
        ]);
    }
}
