<?php

namespace App\Http\Controllers\Hr;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function index(): View
    {
        return view('hr.employees.index', [
            'employees' => Employee::query()
                ->with(['department', 'user'])
                ->orderBy('nip')
                ->paginate(25),
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
            'portal_email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'portal_password' => ['nullable', 'required_with:portal_email', 'string', 'min:12'],
        ]);

        DB::transaction(function () use ($data): void {
            $user = null;

            if (! empty($data['portal_email'])) {
                $user = User::create([
                    'name' => $data['name'],
                    'email' => $data['portal_email'],
                    'password' => $data['portal_password'],
                    'role' => UserRole::Employee,
                    'is_active' => $data['employment_status'] === 'active',
                ]);
            }

            Employee::create([
                'nip' => $data['nip'],
                'name' => $data['name'],
                'department_id' => $data['department_id'],
                'user_id' => $user?->id,
                'join_date' => $data['join_date'] ?? null,
                'employment_status' => $data['employment_status'],
                'attendance_pin_hash' => Hash::make($data['pin']),
                'is_active' => $data['employment_status'] === 'active',
            ]);
        });

        return redirect()->route('hr.employees.index')
            ->with('status', 'Pegawai berhasil dibuat.');
    }

    public function edit(Employee $employee): View
    {
        $employee->load('user');

        return view('hr.employees.edit', [
            'employee' => $employee,
            'departments' => Department::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Employee $employee): RedirectResponse
    {
        $employee->load('user');

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
            'portal_email' => [
                'nullable', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($employee->user_id),
            ],
            'portal_password' => ['nullable', 'string', 'min:12'],
        ]);

        if (! $employee->user && ! empty($data['portal_email']) && empty($data['portal_password'])) {
            throw ValidationException::withMessages([
                'portal_password' => 'Password portal diperlukan untuk membuat akun baru.',
            ]);
        }

        DB::transaction(function () use ($employee, $data): void {
            $isActive = $data['employment_status'] === 'active';

            if ($employee->user) {
                $userData = [
                    'name' => $data['name'],
                    'is_active' => $isActive,
                ];

                if (! empty($data['portal_email'])) {
                    $userData['email'] = $data['portal_email'];
                }

                if (! empty($data['portal_password'])) {
                    $userData['password'] = $data['portal_password'];
                }

                $employee->user->update($userData);
            } elseif (! empty($data['portal_email'])) {
                $user = User::create([
                    'name' => $data['name'],
                    'email' => $data['portal_email'],
                    'password' => $data['portal_password'],
                    'role' => UserRole::Employee,
                    'is_active' => $isActive,
                ]);

                $employee->user_id = $user->id;
            }

            $employee->fill([
                'nip' => $data['nip'],
                'name' => $data['name'],
                'department_id' => $data['department_id'],
                'join_date' => $data['join_date'] ?? null,
                'employment_status' => $data['employment_status'],
                'is_active' => $isActive,
            ]);

            if (! empty($data['pin'])) {
                $employee->attendance_pin_hash = Hash::make($data['pin']);
            }

            $employee->save();
        });

        return redirect()->route('hr.employees.index')
            ->with('status', 'Pegawai berhasil diperbarui.');
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        DB::transaction(function () use ($employee): void {
            $employee->update([
                'employment_status' => 'inactive',
                'is_active' => false,
            ]);

            $employee->user?->update(['is_active' => false]);
        });

        return redirect()->route('hr.employees.index')
            ->with('status', 'Pegawai dinonaktifkan untuk menjaga riwayat data.');
    }
}
