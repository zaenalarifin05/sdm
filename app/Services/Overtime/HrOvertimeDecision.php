<?php

namespace App\Services\Overtime;

use App\Models\OvertimeApproval;
use App\Models\OvertimeRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class HrOvertimeDecision
{
    public function approve(User $hr, OvertimeRequest $request, ?string $note = null): OvertimeRequest
    {
        return $this->decide($hr, $request, 'approved', $note);
    }

    public function reject(User $hr, OvertimeRequest $request, ?string $note = null): OvertimeRequest
    {
        return $this->decide($hr, $request, 'rejected', $note);
    }

    private function decide(User $hr, OvertimeRequest $request, string $action, ?string $note): OvertimeRequest
    {
        if (! $hr->hasAnyRole('hr_admin', 'system_admin')) {
            abort(403);
        }

        if ($request->status !== 'PENDING_HR') {
            throw ValidationException::withMessages([
                'overtime_request' => 'Pengajuan lembur tidak lagi menunggu final approval HR.',
            ]);
        }

        return DB::transaction(function () use ($hr, $request, $action, $note): OvertimeRequest {
            OvertimeApproval::create([
                'overtime_request_id' => $request->id,
                'stage' => 'hr',
                'action' => $action,
                'approver_user_id' => $hr->id,
                'note' => $note,
                'acted_at' => now(),
            ]);

            $request->update([
                'status' => $action === 'approved' ? 'APPROVED' : 'REJECTED_HR',
            ]);

            return $request->fresh(['employee', 'shiftSchedule.shift', 'approvals']);
        });
    }
}
