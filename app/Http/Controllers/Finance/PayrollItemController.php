<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Payroll;
use App\Models\PayrollItem;
use App\Services\Payroll\RecalculatePayrollTotals;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PayrollItemController extends Controller
{
    public function store(
        Request $request,
        Payroll $payroll,
        RecalculatePayrollTotals $recalculate
    ): RedirectResponse {
        $this->assertDraft($payroll);

        $data = $request->validate([
            'type' => ['required', Rule::in(['ALLOWANCE', 'DEDUCTION'])],
            'code' => ['required', 'string', 'max:64'],
            'name' => ['required', 'string', 'max:160'],
            'amount' => ['required', 'numeric', 'gt:0'],
        ]);

        PayrollItem::create([
            'payroll_id' => $payroll->id,
            'type' => $data['type'],
            'code' => strtoupper(trim($data['code'])),
            'name' => trim($data['name']),
            'amount' => $data['amount'],
            'source_type' => 'MANUAL',
        ]);

        $recalculate->execute($payroll);

        return back()->with('status', 'Komponen payroll berhasil ditambahkan.');
    }

    public function destroy(
        Payroll $payroll,
        PayrollItem $payrollItem,
        RecalculatePayrollTotals $recalculate
    ): RedirectResponse {
        $this->assertDraft($payroll);

        if ($payrollItem->payroll_id !== $payroll->id) {
            abort(404);
        }

        $payrollItem->delete();
        $recalculate->execute($payroll);

        return back()->with('status', 'Komponen payroll berhasil dihapus.');
    }

    private function assertDraft(Payroll $payroll): void
    {
        $payroll->loadMissing('period');

        if ($payroll->status !== 'DRAFT' || $payroll->period->status !== 'DRAFT') {
            throw ValidationException::withMessages([
                'payroll' => 'Payroll yang sudah final tidak dapat diubah.',
            ]);
        }
    }
}
