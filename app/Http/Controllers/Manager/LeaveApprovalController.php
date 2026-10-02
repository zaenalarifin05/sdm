<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Services\Leave\ManagerLeaveDecision;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeaveApprovalController extends Controller
{
    public function index(Request $request): View
    {
        $managerEmployee = $request->user()->employee;
        abort_unless($managerEmployee, 403);

        return view('leave.manager.index', [
            'requests' => LeaveRequest::query()
                ->with(['employee.department', 'leaveType'])
                ->whereHas('employee.department', fn ($query) => $query
                    ->where('manager_employee_id', $managerEmployee->id))
                ->where('status', 'PENDING_MANAGER')
                ->oldest()
                ->get(),
        ]);
    }

    public function approve(
        Request $request,
        LeaveRequest $leaveRequest,
        ManagerLeaveDecision $decision
    ): RedirectResponse {
        $data = $request->validate(['note' => ['nullable', 'string', 'max:2000']]);
        $decision->approve($request->user(), $leaveRequest, $data['note'] ?? null);

        return back()->with('status', 'Pengajuan diteruskan ke HR.');
    }

    public function reject(
        Request $request,
        LeaveRequest $leaveRequest,
        ManagerLeaveDecision $decision
    ): RedirectResponse {
        $data = $request->validate(['note' => ['required', 'string', 'max:2000']]);
        $decision->reject($request->user(), $leaveRequest, $data['note']);

        return back()->with('status', 'Pengajuan ditolak oleh atasan.');
    }
}
