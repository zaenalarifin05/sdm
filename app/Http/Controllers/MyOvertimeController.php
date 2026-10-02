<?php

namespace App\Http\Controllers;

use App\Models\OvertimeRequest;
use App\Models\ShiftSchedule;
use App\Services\Overtime\SubmitOvertimeRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MyOvertimeController extends Controller
{
    public function index(Request $request): View
    {
        $employee = $request->user()->employee;
        abort_unless($employee, 404);

        return view('overtime.my.index', [
            'requests' => OvertimeRequest::query()
                ->with(['shiftSchedule.shift', 'shiftSchedule.attendance'])
                ->where('employee_id', $employee->id)
                ->latest()
                ->paginate(20),
        ]);
    }

    public function create(Request $request): View
    {
        $employee = $request->user()->employee;
        abort_unless($employee, 404);

        return view('overtime.my.create', [
            'schedules' => ShiftSchedule::query()
                ->with(['shift', 'attendance'])
                ->where('employee_id', $employee->id)
                ->where('status', 'scheduled')
                ->orderByDesc('work_date')
                ->limit(60)
                ->get(),
        ]);
    }

    public function store(Request $request, SubmitOvertimeRequest $submit): RedirectResponse
    {
        $employee = $request->user()->employee;
        abort_unless($employee, 404);

        $data = $request->validate([
            'shift_schedule_id' => ['required', 'integer', 'exists:shift_schedules,id'],
            'start_at' => ['required', 'date'],
            'end_at' => ['required', 'date'],
            'reason' => ['required', 'string', 'max:2000'],
        ]);

        $submit->execute(
            $employee,
            (int) $data['shift_schedule_id'],
            $data['start_at'],
            $data['end_at'],
            $data['reason'],
        );

        return redirect()->route('my.overtime.index')
            ->with('status', 'Pengajuan lembur berhasil dikirim ke atasan departemen.');
    }
}
