<?php

namespace App\Http\Controllers;

use App\Models\ShiftSchedule;
use App\Models\TemporaryPermission;
use App\Services\TemporaryPermission\RecordTemporaryPermissionMovement;
use App\Services\TemporaryPermission\SubmitTemporaryPermission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MyTemporaryPermissionController extends Controller
{
    public function index(Request $request): View
    {
        $employee = $request->user()->employee;
        abort_unless($employee, 404);

        return view('temporary-permission.my.index', [
            'permissions' => TemporaryPermission::query()
                ->with(['shiftSchedule.shift', 'approver'])
                ->where('employee_id', $employee->id)
                ->latest()
                ->paginate(20),
        ]);
    }

    public function create(Request $request): View
    {
        $employee = $request->user()->employee;
        abort_unless($employee, 404);

        return view('temporary-permission.my.create', [
            'schedules' => ShiftSchedule::query()
                ->with(['shift', 'attendance'])
                ->where('employee_id', $employee->id)
                ->where('status', 'scheduled')
                ->orderByDesc('work_date')
                ->limit(30)
                ->get(),
        ]);
    }

    public function store(Request $request, SubmitTemporaryPermission $submit): RedirectResponse
    {
        $employee = $request->user()->employee;
        abort_unless($employee, 404);

        $data = $request->validate([
            'shift_schedule_id' => ['required', 'integer', 'exists:shift_schedules,id'],
            'reason' => ['required', 'string', 'max:2000'],
        ]);

        $submit->execute(
            $employee,
            (int) $data['shift_schedule_id'],
            $data['reason'],
        );

        return redirect()->route('my.temporary-permissions.index')
            ->with('status', 'Izin keluar berhasil diajukan ke atasan departemen.');
    }

    public function start(
        Request $request,
        TemporaryPermission $temporaryPermission,
        RecordTemporaryPermissionMovement $movement
    ): RedirectResponse {
        $movement->start($request->user(), $temporaryPermission);

        return back()->with('status', 'Waktu keluar berhasil dicatat.');
    }

    public function return(
        Request $request,
        TemporaryPermission $temporaryPermission,
        RecordTemporaryPermissionMovement $movement
    ): RedirectResponse {
        $permission = $movement->return($request->user(), $temporaryPermission);

        return back()->with(
            'status',
            'Waktu kembali dicatat. Durasi izin: '.$permission->duration_minutes.' menit.'
        );
    }
}
