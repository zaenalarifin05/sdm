<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\PayrollPolicyVersion;
use App\Services\Payroll\PayrollMoney;
use App\Services\Payroll\PayrollPolicyResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PayrollPolicyController extends Controller
{
    public function index(): View
    {
        return view('finance.payroll.policies', [
            'versions' => PayrollPolicyVersion::query()
                ->with('creator')
                ->orderByDesc('effective_from')
                ->orderByDesc('id')
                ->get(),
            'policyKeys' => PayrollPolicyResolver::KEYS,
        ]);
    }

    public function store(Request $request, PayrollMoney $money): RedirectResponse
    {
        $data = $request->validate([
            'policy_key' => ['required', Rule::in(PayrollPolicyResolver::KEYS)],
            'enabled' => ['nullable', 'boolean'],
            'mode' => ['nullable', 'string', 'max:64'],
            'value' => ['nullable', 'string', 'max:64'],
            'effective_from' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $enabled = $request->boolean('enabled');

        if ($enabled) {
            $allowedModes = match ($data['policy_key']) {
                'absent_deduction' => ['fixed_per_day', 'salary_divisor_per_day'],
                'late_deduction' => ['fixed_per_minute', 'fixed_per_incident'],
                'overtime_pay' => ['fixed_per_minute', 'fixed_per_hour'],
            };

            if (! in_array($data['mode'] ?? null, $allowedModes, true)) {
                throw ValidationException::withMessages([
                    'mode' => 'Mode policy tidak valid untuk jenis policy ini.',
                ]);
            }

            if (($data['mode'] ?? null) === 'salary_divisor_per_day') {
                if (! ctype_digit((string) ($data['value'] ?? ''))
                    || (int) $data['value'] < 1
                    || (int) $data['value'] > 366) {
                    throw ValidationException::withMessages([
                        'value' => 'Divisor harus berupa bilangan bulat antara 1 dan 366.',
                    ]);
                }
            } else {
                try {
                    $cents = $money->toCents((string) ($data['value'] ?? ''));
                } catch (ValidationException) {
                    throw ValidationException::withMessages([
                        'value' => 'Nilai nominal policy tidak valid.',
                    ]);
                }

                if ($cents < 1) {
                    throw ValidationException::withMessages([
                        'value' => 'Nilai nominal policy harus lebih dari 0.',
                    ]);
                }
            }
        }

        $exists = PayrollPolicyVersion::query()
            ->where('policy_key', $data['policy_key'])
            ->whereDate('effective_from', $data['effective_from'])
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'effective_from' => 'Versi policy untuk tanggal efektif tersebut sudah ada.',
            ]);
        }

        PayrollPolicyVersion::create([
            'policy_key' => $data['policy_key'],
            'enabled' => $enabled,
            'mode' => $enabled ? $data['mode'] : null,
            'value' => $enabled ? $data['value'] : null,
            'effective_from' => $data['effective_from'],
            'notes' => $data['notes'] ?? null,
            'created_by' => $request->user()->id,
        ]);

        return back()->with('status', 'Versi payroll policy berhasil ditambahkan.');
    }
}
