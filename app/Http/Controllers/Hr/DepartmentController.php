<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DepartmentController extends Controller
{
    public function index(): View
    {
        return view('hr.departments.index', [
            'departments' => Department::query()->withCount('employees')->orderBy('code')->get(),
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

        return redirect()->route('hr.departments.index')->with('status', 'Departemen berhasil dibuat.');
    }

    public function edit(Department $department): View
    {
        return view('hr.departments.edit', compact('department'));
    }

    public function update(Request $request, Department $department): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:20', Rule::unique('departments', 'code')->ignore($department)],
            'name' => ['required', 'string', 'max:120'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $department->update($data);

        return redirect()->route('hr.departments.index')->with('status', 'Departemen berhasil diperbarui.');
    }

    public function destroy(Department $department): RedirectResponse
    {
        if ($department->employees()->exists()) {
            return back()->withErrors(['department' => 'Departemen yang masih memiliki pegawai tidak dapat dihapus.']);
        }

        $department->delete();

        return redirect()->route('hr.departments.index')->with('status', 'Departemen berhasil dihapus.');
    }
}
