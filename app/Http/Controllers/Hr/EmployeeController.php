<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function index(): View
    {
        return view('hr.employees.index', [
            'employees' => Employee::query()->with('department')->orderBy('nip')->paginate(25),
        ]);
    }

    public function create(): View
    {
        return view('hr.employees.create', [
            'departments' => Department::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nip' => ['required', 'string', 'max:32', 'unique:employees,nip'],
            'name' => ['required', 'string', 'max:160'],
            'department_id' => [
                'required',
                Rule::exists('departments', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'join_date' => ['nullable', 'date'],
            'employment_status' => ['required', Rule::in(['active', 'inactive', 'resigned'])],
            'pin' => ['required', 'digits:6'],
        ]);

        Employee::create([
            'nip' => $data['nip'],
            'name' => $data['name'],
            'department_id' => $data['department_id'],
            'join_date' => $data['join_date'] ?? null,
            'employment_status' => $data['employment_status'],
            'attendance_pin_hash' => Hash::make($data['pin']),
            'is_active' => $data['employment_status'] === 'active',
        ]);

        return redirect()->route('hr.employees.index')->with('status', 'Pegawai berhasil dibuat.');
    }

    public function edit(Employee $employee): View
    {
        return view('hr.employees.edit', [
            'employee' => $employee,
            'departments' => Department::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Employee $employee): RedirectResponse
    {
        $data = $request->validate([
            'nip' => ['required', 'string', 'max:32', Rule::unique('employees', 'nip')->ignore($employee)],
            'name' => ['required', 'string', 'max:160'],
            'department_id' => [
                'required',
                Rule::exists('departments', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'join_date' => ['nullable', 'date'],
            'employment_status' => ['required', Rule::in(['active', 'inactive', 'resigned'])],
            'pin' => ['nullable', 'digits:6'],
        ]);

        $update = [
            'nip' => $data['nip'],
            'name' => $data['name'],
            'department_id' => $data['department_id'],
            'join_date' => $data['join_date'] ?? null,
            'employment_status' => $data['employment_status'],
            'is_active' => $data['employment_status'] === 'active',
        ];

        if (! empty($data['pin'])) {
            $update['attendance_pin_hash'] = Hash::make($data['pin']);
        }

        $employee->update($update);

        return redirect()->route('hr.employees.index')->with('status', 'Pegawai berhasil diperbarui.');
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        $employee->update([
            'employment_status' => 'inactive',
            'is_active' => false,
        ]);

        return redirect()->route('hr.employees.index')->with('status', 'Pegawai dinonaktifkan untuk menjaga riwayat data.');
    }
}
