<?php

namespace App\Http\Controllers\Hr;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class DepartmentController extends Controller
{
    public function index(): View
    {
        return view('hr.departments.index', [
            'departments' => Department::query()
                ->with('manager')
                ->withCount('employees')
                ->orderBy('code')
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('hr.departments.create');
    }

    public function store(Request $request): RedirectResponse
    {
        Department::create($request->validate([
            'code' => ['required', 'string', 'max:20', 'unique:departments,code'],
            'name' => ['required', 'string', 'max:120'],
        ]) + ['is_active' => true]);

        return redirect()->route('hr.departments.index')
            ->with('status', 'Departemen berhasil dibuat.');
    }

    public function edit(Department $department): View
    {
        return view('hr.departments.edit', [
            'department' => $department,
            'managerCandidates' => Employee::query()
                ->with('user')
                ->where('department_id', $department->id)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function update(Request $request, Department $department): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:20', Rule::unique('departments', 'code')->ignore($department)],
            'name' => ['required', 'string', 'max:120'],
            'manager_employee_id' => ['nullable', 'integer', 'exists:employees,id'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $newManager = null;

        if (! empty($data['manager_employee_id'])) {
            $newManager = Employee::query()->with('user')->findOrFail($data['manager_employee_id']);
            $role = $newManager->user?->role?->value;

            if ($newManager->department_id !== $department->id
                || ! $newManager->is_active
                || ! $newManager->user?->is_active
                || ! in_array($role, ['employee', 'manager'], true)) {
                throw ValidationException::withMessages([
                    'manager_employee_id' => 'Manager harus pegawai aktif di departemen ini dengan akun portal Employee/Manager.',
                ]);
            }
        }

        DB::transaction(function () use ($department, $data, $newManager, $request): void {
            $previousManager = $department->manager()->with('user')->first();

            $department->update([
                'code' => $data['code'],
                'name' => $data['name'],
                'manager_employee_id' => $newManager?->id,
                'is_active' => $request->boolean('is_active'),
            ]);

            if ($newManager?->user) {
                $newManager->user->update(['role' => UserRole::Manager]);
            }

            if ($previousManager?->user
                && $previousManager->id !== $newManager?->id
                && $previousManager->user->role === UserRole::Manager) {
                $stillManages = Department::query()
                    ->where('manager_employee_id', $previousManager->id)
                    ->exists();

                if (! $stillManages) {
                    $previousManager->user->update(['role' => UserRole::Employee]);
                }
            }
        });

        return redirect()->route('hr.departments.index')
            ->with('status', 'Departemen berhasil diperbarui.');
    }

    public function destroy(Department $department): RedirectResponse
    {
        if ($department->employees()->exists()) {
            return back()->withErrors([
                'department' => 'Departemen yang masih memiliki pegawai tidak dapat dihapus.',
            ]);
        }

        $department->delete();

        return redirect()->route('hr.departments.index')
            ->with('status', 'Departemen berhasil dihapus.');
    }
}
