<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\OvertimeRequest;
use App\Services\Overtime\ManagerOvertimeDecision;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OvertimeApprovalController extends Controller
{
    public function index(Request $request): View
    {
        $managerEmployee = $request->user()->employee;
        abort_unless($managerEmployee, 403);

        return view('overtime.manager.index', [
            'requests' => OvertimeRequest::query()
                ->with(['employee.department', 'shiftSchedule.shift', 'shiftSchedule.attendance'])
                ->whereHas('employee.department', fn ($query) => $query
                    ->where('manager_employee_id', $managerEmployee->id))
                ->where('status', 'PENDING_MANAGER')
                ->oldest()
                ->get(),
        ]);
    }

    public function approve(
        Request $request,
        OvertimeRequest $overtimeRequest,
        ManagerOvertimeDecision $decision
    ): RedirectResponse {
        $data = $request->validate(['note' => ['nullable', 'string', 'max:2000']]);
        $decision->approve($request->user(), $overtimeRequest, $data['note'] ?? null);

        return back()->with('status', 'Pengajuan lembur diteruskan ke HR.');
    }

    public function reject(
        Request $request,
        OvertimeRequest $overtimeRequest,
        ManagerOvertimeDecision $decision
    ): RedirectResponse {
        $data = $request->validate(['note' => ['required', 'string', 'max:2000']]);
        $decision->reject($request->user(), $overtimeRequest, $data['note']);

        return back()->with('status', 'Pengajuan lembur ditolak oleh atasan.');
    }
}
