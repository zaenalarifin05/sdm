<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\PayrollPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PayrollPeriodController extends Controller
{
    public function index(): View
    {
        return view('finance.payroll.index', [
            'periods' => PayrollPeriod::query()
                ->withCount('payrolls')
                ->orderByDesc('period_start')
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
        ]);

        $overlap = PayrollPeriod::query()
            ->whereDate('period_start', '<=', $data['period_end'])
            ->whereDate('period_end', '>=', $data['period_start'])
            ->exists();

        if ($overlap) {
            throw ValidationException::withMessages([
                'period_start' => 'Payroll period tidak boleh bertumpang tindih.',
            ]);
        }

        $period = PayrollPeriod::create([
            'name' => $data['name'],
            'period_start' => $data['period_start'],
            'period_end' => $data['period_end'],
            'status' => 'DRAFT',
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('finance.payroll.show', $period)
            ->with('status', 'Payroll period berhasil dibuat.');
    }

    public function show(PayrollPeriod $payrollPeriod): View
    {
        return view('finance.payroll.show', [
            'period' => $payrollPeriod,
            'payrolls' => $payrollPeriod->payrolls()
                ->with('employee.department')
                ->orderBy('employee_id')
                ->get(),
        ]);
    }
}
