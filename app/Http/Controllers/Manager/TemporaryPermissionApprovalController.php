<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\TemporaryPermission;
use App\Services\TemporaryPermission\ManagerTemporaryPermissionDecision;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TemporaryPermissionApprovalController extends Controller
{
    public function index(Request $request): View
    {
        $managerEmployee = $request->user()->employee;
        abort_unless($managerEmployee, 403);

        return view('temporary-permission.manager.index', [
            'permissions' => TemporaryPermission::query()
                ->with(['employee.department', 'shiftSchedule.shift'])
                ->whereHas('employee.department', fn ($query) => $query
                    ->where('manager_employee_id', $managerEmployee->id))
                ->where('status', 'PENDING_MANAGER')
                ->oldest()
                ->get(),
        ]);
    }

    public function approve(
        Request $request,
        TemporaryPermission $temporaryPermission,
        ManagerTemporaryPermissionDecision $decision
    ): RedirectResponse {
        $data = $request->validate(['note' => ['nullable', 'string', 'max:2000']]);
        $decision->approve($request->user(), $temporaryPermission, $data['note'] ?? null);

        return back()->with('status', 'Izin keluar disetujui.');
    }

    public function reject(
        Request $request,
        TemporaryPermission $temporaryPermission,
        ManagerTemporaryPermissionDecision $decision
    ): RedirectResponse {
        $data = $request->validate(['note' => ['required', 'string', 'max:2000']]);
        $decision->reject($request->user(), $temporaryPermission, $data['note']);

        return back()->with('status', 'Izin keluar ditolak.');
    }
}
