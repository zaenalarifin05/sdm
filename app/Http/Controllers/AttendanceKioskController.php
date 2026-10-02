<?php

namespace App\Http\Controllers;

use App\Exceptions\AttendancePunchException;
use App\Services\Attendance\RecordAttendancePunch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AttendanceKioskController extends Controller
{
    public function create(): View
    {
        return view('attendance.kiosk');
    }

    public function store(Request $request, RecordAttendancePunch $recordAttendancePunch): RedirectResponse
    {
        $data = $request->validate([
            'nip' => ['required', 'string', 'max:32'],
            'pin' => ['required', 'digits:6'],
        ]);

        try {
            $result = $recordAttendancePunch->execute($data['nip'], $data['pin']);
        } catch (AttendancePunchException $exception) {
            throw ValidationException::withMessages([
                'nip' => $exception->getMessage(),
            ]);
        }

        $attendance = $result->attendance;

        return redirect()->route('attendance.create')->with('attendance_result', [
            'action' => $result->action,
            'employee' => $attendance->employee->name,
            'shift' => $attendance->shiftSchedule->shift->name,
            'time' => ($result->action === 'check_in' ? $attendance->check_in_at : $attendance->check_out_at)
                ->setTimezone(config('app.timezone'))
                ->format('H:i:s'),
        ]);
    }
}
