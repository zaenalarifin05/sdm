<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Shift;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ShiftController extends Controller
{
    public function index(): View
    {
        return view('hr.shifts.index', [
            'shifts' => Shift::query()->orderBy('start_time')->get(),
        ]);
    }

    public function create(): View
    {
        return view('hr.shifts.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:20', 'unique:shifts,code'],
            'name' => ['required', 'string', 'max:80'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
        ]);

        Shift::create($data + [
            'crosses_midnight' => $data['end_time'] <= $data['start_time'],
            'is_active' => true,
        ]);

        return redirect()->route('hr.shifts.index')->with('status', 'Shift berhasil dibuat.');
    }

    public function edit(Shift $shift): View
    {
        return view('hr.shifts.edit', compact('shift'));
    }

    public function update(Request $request, Shift $shift): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:20', Rule::unique('shifts', 'code')->ignore($shift)],
            'name' => ['required', 'string', 'max:80'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $shift->update([
            'code' => $data['code'],
            'name' => $data['name'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'crosses_midnight' => $data['end_time'] <= $data['start_time'],
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('hr.shifts.index')->with('status', 'Shift berhasil diperbarui.');
    }

    public function destroy(Shift $shift): RedirectResponse
    {
        $shift->update(['is_active' => false]);

        return redirect()->route('hr.shifts.index')->with('status', 'Shift dinonaktifkan agar riwayat jadwal tetap utuh.');
    }
}
