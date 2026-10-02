<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Holiday;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class HolidayController extends Controller
{
    public function index(): View
    {
        return view('hr.holidays.index', [
            'holidays' => Holiday::query()->orderByDesc('holiday_date')->paginate(30),
        ]);
    }

    public function create(): View
    {
        return view('hr.holidays.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'holiday_date' => ['required', 'date', 'unique:holidays,holiday_date'],
            'name' => ['required', 'string', 'max:160'],
            'type' => ['required', Rule::in(['national', 'company'])],
            'reference' => ['nullable', 'string', 'max:255'],
        ]);

        Holiday::create($data + ['is_active' => true]);

        return redirect()->route('hr.holidays.index')
            ->with('status', 'Hari libur berhasil ditambahkan.');
    }

    public function edit(Holiday $holiday): View
    {
        return view('hr.holidays.edit', compact('holiday'));
    }

    public function update(Request $request, Holiday $holiday): RedirectResponse
    {
        $data = $request->validate([
            'holiday_date' => [
                'required',
                'date',
                Rule::unique('holidays', 'holiday_date')->ignore($holiday),
            ],
            'name' => ['required', 'string', 'max:160'],
            'type' => ['required', Rule::in(['national', 'company'])],
            'reference' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $holiday->update($data);

        return redirect()->route('hr.holidays.index')
            ->with('status', 'Hari libur berhasil diperbarui.');
    }

    public function destroy(Holiday $holiday): RedirectResponse
    {
        $holiday->update(['is_active' => false]);

        return redirect()->route('hr.holidays.index')
            ->with('status', 'Hari libur dinonaktifkan agar riwayat tetap tersimpan.');
    }
}
