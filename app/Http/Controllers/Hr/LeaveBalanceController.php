<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LeaveBalanceController extends Controller
{
    public function index(Request $request): View
    {
        $year = (int) ($request->input('year') ?: now()->year);

        return view('leave.hr.balances', [
            'year' => $year,
            'balances' => LeaveBalance::query()
                ->with(['employee.department', 'leaveType'])
                ->where('year', $year)
                ->orderBy('employee_id')
                ->get(),
            'employees' => Employee::query()->where('is_active', true)->orderBy('name')->get(),
            'leaveTypes' => LeaveType::query()
                ->where('is_active', true)
                ->where('deduct_balance', true)
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'employee_id' => ['required', Rule::exists('employees', 'id')->where('is_active', true)],
            'leave_type_id' => ['required', 'exists:leave_types,id'],
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'allocated_days' => ['required', 'integer', 'min:0', 'max:366'],
        ]);

        $leaveType = LeaveType::query()->findOrFail($data['leave_type_id']);

        if (! $leaveType->deduct_balance) {
            throw ValidationException::withMessages([
                'leave_type_id' => 'Jenis izin ini tidak menggunakan saldo.',
            ]);
        }

        $balance = LeaveBalance::query()->firstOrNew([
            'employee_id' => $data['employee_id'],
            'leave_type_id' => $data['leave_type_id'],
            'year' => $data['year'],
        ]);

        if ($balance->exists && $data['allocated_days'] < $balance->used_days) {
            throw ValidationException::withMessages([
                'allocated_days' => 'Alokasi tidak boleh lebih kecil dari saldo yang sudah digunakan.',
            ]);
        }

        $balance->allocated_days = $data['allocated_days'];
        $balance->used_days ??= 0;
        $balance->save();

        return redirect()->route('hr.leave-balances.index', ['year' => $data['year']])
            ->with('status', 'Saldo cuti berhasil disimpan.');
    }
}
