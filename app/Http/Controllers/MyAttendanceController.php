<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class MyAttendanceController extends Controller
{
    public function __invoke(Request $request): View
    {
        $employee = $request->user()->employee;

        abort_unless($employee, 404);

        return view('attendance.history', [
            'employee' => $employee,
            'attendances' => $employee->attendances()
                ->with('shiftSchedule.shift')
                ->orderByDesc('work_date')
                ->paginate(30),
        ]);
    }
}
