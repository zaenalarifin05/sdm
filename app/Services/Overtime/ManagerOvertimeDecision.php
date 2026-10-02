<?php

namespace App\Services\Overtime;

use App\Models\OvertimeApproval;
use App\Models\OvertimeRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ManagerOvertimeDecision
{
    public function approve(User $manager, OvertimeRequest $request, ?string $note = null): OvertimeRequest
    {
        return $this->decide($manager, $request, 'approved', $note);
    }

    public function reject(User $manager, OvertimeRequest $request, ?string $note = null): OvertimeRequest
    {
        return $this->decide($manager, $request, 'rejected', $note);
    }

    private function decide(User $manager, OvertimeRequest $request, string $action, ?string $note): OvertimeRequest
    {
        $manager->loadMissing('employee');
        $request->loadMissing('employee.department');

        if (! $manager->employee
            || $request->employee->department?->manager_employee_id !== $manager->employee->id) {
            abort(403);
        }

        if ($request->status !== 'PENDING_MANAGER') {
            throw ValidationException::withMessages([
                'overtime_request' => 'Pengajuan lembur tidak lagi menunggu approval atasan.',
            ]);
        }

        return DB::transaction(function () use ($manager, $request, $action, $note): OvertimeRequest {
            OvertimeApproval::create([
                'overtime_request_id' => $request->id,
                'stage' => 'manager',
                'action' => $action,
                'approver_user_id' => $manager->id,
                'note' => $note,
                'acted_at' => now(),
            ]);

            $request->update([
                'status' => $action === 'approved' ? 'PENDING_HR' : 'REJECTED_MANAGER',
            ]);

            return $request->fresh(['employee', 'shiftSchedule.shift', 'approvals']);
        });
    }
}
