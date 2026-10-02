<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\ShiftSchedule;
use App\Services\Attendance\ProcessAttendanceForWorkDate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttendanceMonitorController extends Controller
{
    public function index(Request $request): View
    {
        $workDate = $request->string('work_date')->toString() ?: now(config('app.timezone'))->toDateString();

        $schedules = ShiftSchedule::query()
            ->with(['employee.department', 'shift', 'attendance'])
            ->whereDate('work_date', $workDate)
            ->where('status', 'scheduled')
            ->orderBy('shift_id')
            ->orderBy('employee_id')
            ->get();

        return view('hr.attendance.index', compact('workDate', 'schedules'));
    }

    public function process(Request $request, ProcessAttendanceForWorkDate $processor): RedirectResponse
    {
        $data = $request->validate([
            'work_date' => ['required', 'date'],
        ]);

        $result = $processor->execute($data['work_date']);
        $processed = $result->where('processed', true)->count();

        return redirect()
            ->route('hr.attendance.index', ['work_date' => $data['work_date']])
            ->with('status', "Pemrosesan presensi selesai: {$processed} jadwal diproses.");
    }
}
