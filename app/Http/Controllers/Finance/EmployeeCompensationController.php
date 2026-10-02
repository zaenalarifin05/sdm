<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeCompensation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class EmployeeCompensationController extends Controller
{
    public function index(): View
    {
        return view('finance.compensations.index', [
            'employees' => Employee::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
            'compensations' => EmployeeCompensation::query()
                ->with('employee.department')
                ->orderBy('employee_id')
                ->orderByDesc('effective_from')
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'effective_from' => ['required', 'date'],
            'base_salary' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $exists = EmployeeCompensation::query()
            ->where('employee_id', $data['employee_id'])
            ->whereDate('effective_from', $data['effective_from'])
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'effective_from' => 'Compensation untuk pegawai dan tanggal efektif tersebut sudah ada.',
            ]);
        }

        EmployeeCompensation::create([
            'employee_id' => $data['employee_id'],
            'effective_from' => $data['effective_from'],
            'base_salary' => $data['base_salary'],
            'currency' => 'IDR',
            'notes' => $data['notes'] ?? null,
        ]);

        return back()->with('status', 'Riwayat compensation berhasil ditambahkan.');
    }
}
