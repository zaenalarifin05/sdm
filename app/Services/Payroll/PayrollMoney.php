<?php

namespace App\Services\Payroll;

use Illuminate\Validation\ValidationException;

class PayrollMoney
{
    public function toCents(string|int $amount): int
    {
        $normalized = trim((string) $amount);

        if (! preg_match('/^-?\d+(?:\.\d{1,2})?$/', $normalized)) {
            throw ValidationException::withMessages([
                'payroll_policy' => 'Nilai uang policy tidak valid.',
            ]);
        }

        $negative = str_starts_with($normalized, '-');

        if ($negative) {
            $normalized = substr($normalized, 1);
        }

        if (! str_contains($normalized, '.')) {
            $cents = ((int) $normalized) * 100;
        } else {
            [$whole, $fraction] = explode('.', $normalized, 2);
            $fraction = substr(str_pad($fraction, 2, '0'), 0, 2);
            $cents = ((int) $whole) * 100 + (int) $fraction;
        }

        return $negative ? -$cents : $cents;
    }

    public function fromCents(int $cents): string
    {
        $sign = $cents < 0 ? '-' : '';
        $absolute = abs($cents);

        return $sign.intdiv($absolute, 100).'.'.str_pad(
            (string) ($absolute % 100),
            2,
            '0',
            STR_PAD_LEFT
        );
    }
}
