<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\OvertimeRequest;
use App\Services\Overtime\HrOvertimeDecision;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OvertimeApprovalController extends Controller
{
    public function index(): View
    {
        return view('overtime.hr.index', [
            'requests' => OvertimeRequest::query()
                ->with([
                    'employee.department',
                    'shiftSchedule.shift',
                    'shiftSchedule.attendance',
                    'approvals.approver',
                ])
                ->where('status', 'PENDING_HR')
                ->oldest()
                ->get(),
        ]);
    }

    public function approve(
        Request $request,
        OvertimeRequest $overtimeRequest,
        HrOvertimeDecision $decision
    ): RedirectResponse {
        $data = $request->validate(['note' => ['nullable', 'string', 'max:2000']]);
        $decision->approve($request->user(), $overtimeRequest, $data['note'] ?? null);

        return back()->with('status', 'Pengajuan lembur disetujui final oleh HR.');
    }

    public function reject(
        Request $request,
        OvertimeRequest $overtimeRequest,
        HrOvertimeDecision $decision
    ): RedirectResponse {
        $data = $request->validate(['note' => ['required', 'string', 'max:2000']]);
        $decision->reject($request->user(), $overtimeRequest, $data['note']);

        return back()->with('status', 'Pengajuan lembur ditolak oleh HR.');
    }
}
