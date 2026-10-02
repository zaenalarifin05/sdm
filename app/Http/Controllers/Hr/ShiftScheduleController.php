<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Shift;
use App\Models\ShiftSchedule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ShiftScheduleController extends Controller
{
    public function index(Request $request): View
    {
        $query = ShiftSchedule::query()->with(['employee.department', 'shift'])->orderByDesc('work_date');

        if ($request->filled('work_date')) {
            $query->whereDate('work_date', $request->string('work_date')->toString());
        }

        return view('hr.schedules.index', [
            'schedules' => $query->paginate(30)->withQueryString(),
        ]);
    }

    public function create(): View
    {
        return view('hr.schedules.create', [
            'employees' => Employee::query()->where('is_active', true)->orderBy('name')->get(),
            'shifts' => Shift::query()->where('is_active', true)->orderBy('start_time')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'employee_id' => [
                'required',
                Rule::exists('employees', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'shift_id' => [
                'required',
                Rule::exists('shifts', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'work_date' => ['required', 'date'],
        ]);

        if (ShiftSchedule::query()
            ->where('employee_id', $data['employee_id'])
            ->whereDate('work_date', $data['work_date'])
            ->exists()) {
            throw ValidationException::withMessages([
                'work_date' => 'Pegawai sudah memiliki jadwal pada tanggal tersebut.',
            ]);
        }

        ShiftSchedule::create($data + [
            'status' => 'scheduled',
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('hr.schedules.index')->with('status', 'Jadwal shift berhasil ditetapkan.');
    }

    public function edit(ShiftSchedule $schedule): View
    {
        return view('hr.schedules.edit', [
            'schedule' => $schedule,
            'employees' => Employee::query()->where('is_active', true)->orderBy('name')->get(),
            'shifts' => Shift::query()->where('is_active', true)->orderBy('start_time')->get(),
        ]);
    }

    public function update(Request $request, ShiftSchedule $schedule): RedirectResponse
    {
        $data = $request->validate([
            'employee_id' => [
                'required',
                Rule::exists('employees', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'shift_id' => [
                'required',
                Rule::exists('shifts', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'work_date' => ['required', 'date'],
            'status' => ['required', Rule::in(['scheduled', 'cancelled'])],
        ]);

        if (ShiftSchedule::query()
            ->where('employee_id', $data['employee_id'])
            ->whereDate('work_date', $data['work_date'])
            ->whereKeyNot($schedule->getKey())
            ->exists()) {
            throw ValidationException::withMessages([
                'work_date' => 'Pegawai sudah memiliki jadwal pada tanggal tersebut.',
            ]);
        }

        $schedule->update($data);

        return redirect()->route('hr.schedules.index')->with('status', 'Jadwal shift berhasil diperbarui.');
    }

    public function destroy(ShiftSchedule $schedule): RedirectResponse
    {
        $schedule->update(['status' => 'cancelled']);

        return redirect()->route('hr.schedules.index')->with('status', 'Jadwal shift dibatalkan.');
    }
}
