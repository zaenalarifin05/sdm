<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Payroll;
use App\Models\PayrollPeriod;
use App\Support\CsvExport;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PayrollReportController extends Controller
{
    public function index(Request $request): View
    {
        $periodId = $request->integer('payroll_period_id') ?: null;
        $period = $periodId ? PayrollPeriod::findOrFail($periodId) : PayrollPeriod::query()
            ->orderByDesc('period_start')->first();

        $payrolls = $period
            ? Payroll::query()->where('payroll_period_id', $period->id)
            : Payroll::query()->whereRaw('1 = 0');

        return view('finance.reports.payroll', [
            'periods' => PayrollPeriod::query()->orderByDesc('period_start')->get(),
            'period' => $period,
            'summary' => [
                'employees' => (clone $payrolls)->count(),
                'gross_pay' => (string) (clone $payrolls)->sum('gross_pay'),
                'deduction' => (string) (clone $payrolls)->sum('total_deduction'),
                'net_pay' => (string) (clone $payrolls)->sum('net_pay'),
            ],
        ]);
    }

    public function csv(Request $request): StreamedResponse
    {
        $data = $request->validate([
            'payroll_period_id' => ['required', 'integer', 'exists:payroll_periods,id'],
        ]);

        $period = PayrollPeriod::findOrFail($data['payroll_period_id']);

        $rows = Payroll::query()
            ->with('employee.department')
            ->where('payroll_period_id', $period->id)
            ->orderBy('employee_id')
            ->cursor()
            ->map(fn (Payroll $payroll) => [
                $period->name,
                $payroll->employee->nip,
                $payroll->employee->name,
                $payroll->employee->department->name,
                $payroll->status,
                $payroll->base_salary_snapshot,
                $payroll->total_allowance,
                $payroll->gross_pay,
                $payroll->total_deduction,
                $payroll->net_pay,
                $payroll->scheduled_days,
                $payroll->present_days,
                $payroll->late_days,
                $payroll->late_minutes,
                $payroll->absent_days,
                $payroll->leave_days,
                $payroll->permit_days,
                $payroll->approved_overtime_minutes,
            ]);

        return CsvExport::download(
            'payroll-recap-'.$period->period_start->format('Y-m').'.csv',
            ['Period','NIP','Employee','Department','Status','Base Salary','Allowance','Gross Pay','Deduction','Net Pay','Scheduled Days','Present Days','Late Days','Late Minutes','Absent Days','Leave Days','Permit Days','Approved Overtime Minutes'],
            $rows
        );
    }
}
