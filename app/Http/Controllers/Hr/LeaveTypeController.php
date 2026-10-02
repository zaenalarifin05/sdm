<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\LeaveType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LeaveTypeController extends Controller
{
    public function index(): View
    {
        return view('leave.hr.types', [
            'types' => LeaveType::query()->orderBy('code')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:32', 'unique:leave_types,code'],
            'name' => ['required', 'string', 'max:120'],
            'category' => ['required', Rule::in(['leave', 'permit'])],
            'deduct_balance' => ['nullable', 'boolean'],
        ]);

        LeaveType::create([
            'code' => strtoupper($data['code']),
            'name' => $data['name'],
            'category' => $data['category'],
            'deduct_balance' => $request->boolean('deduct_balance'),
            'is_active' => true,
        ]);

        return back()->with('status', 'Jenis cuti/izin berhasil dibuat.');
    }

    public function update(Request $request, LeaveType $leaveType): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'category' => ['required', Rule::in(['leave', 'permit'])],
            'deduct_balance' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $leaveType->update([
            'name' => $data['name'],
            'category' => $data['category'],
            'deduct_balance' => $request->boolean('deduct_balance'),
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('status', 'Jenis cuti/izin berhasil diperbarui.');
    }
}
