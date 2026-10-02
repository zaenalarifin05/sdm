<?php

namespace App\Services\Leave;

use App\Models\LeaveApproval;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ManagerLeaveDecision
{
    public function approve(User $manager, LeaveRequest $request, ?string $note = null): LeaveRequest
    {
        return $this->decide($manager, $request, 'approved', $note);
    }

    public function reject(User $manager, LeaveRequest $request, ?string $note = null): LeaveRequest
    {
        return $this->decide($manager, $request, 'rejected', $note);
    }

    private function decide(User $manager, LeaveRequest $request, string $action, ?string $note): LeaveRequest
    {
        $manager->loadMissing('employee');
        $request->loadMissing('employee.department');

        if (! $manager->employee
            || $request->employee->department?->manager_employee_id !== $manager->employee->id) {
            abort(403);
        }

        if ($request->status !== 'PENDING_MANAGER') {
            throw ValidationException::withMessages([
                'leave_request' => 'Pengajuan tidak lagi menunggu approval atasan.',
            ]);
        }

        return DB::transaction(function () use ($manager, $request, $action, $note): LeaveRequest {
            LeaveApproval::create([
                'leave_request_id' => $request->id,
                'stage' => 'manager',
                'action' => $action,
                'approver_user_id' => $manager->id,
                'note' => $note,
                'acted_at' => now(),
            ]);

            $request->update([
                'status' => $action === 'approved' ? 'PENDING_HR' : 'REJECTED_MANAGER',
            ]);

            return $request->fresh(['employee', 'leaveType', 'approvals']);
        });
    }
}
