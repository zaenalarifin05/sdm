<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Services\Leave\HrLeaveDecision;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeaveApprovalController extends Controller
{
    public function index(): View
    {
        return view('leave.hr.index', [
            'requests' => LeaveRequest::query()
                ->with(['employee.department', 'leaveType', 'approvals.approver'])
                ->where('status', 'PENDING_HR')
                ->oldest()
                ->get(),
        ]);
    }

    public function approve(
        Request $request,
        LeaveRequest $leaveRequest,
        HrLeaveDecision $decision
    ): RedirectResponse {
        $data = $request->validate(['note' => ['nullable', 'string', 'max:2000']]);
        $decision->approve($request->user(), $leaveRequest, $data['note'] ?? null);

        return back()->with('status', 'Pengajuan disetujui final oleh HR.');
    }

    public function reject(
        Request $request,
        LeaveRequest $leaveRequest,
        HrLeaveDecision $decision
    ): RedirectResponse {
        $data = $request->validate(['note' => ['required', 'string', 'max:2000']]);
        $decision->reject($request->user(), $leaveRequest, $data['note']);

        return back()->with('status', 'Pengajuan ditolak oleh HR.');
    }
}
