<?php

namespace App\Services\Leave;

use App\Models\LeaveApproval;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class HrLeaveDecision
{
    public function approve(User $hr, LeaveRequest $request, ?string $note = null): LeaveRequest
    {
        if (! $hr->hasAnyRole('hr_admin', 'system_admin')) {
            abort(403);
        }

        if ($request->status !== 'PENDING_HR') {
            throw ValidationException::withMessages([
                'leave_request' => 'Pengajuan tidak lagi menunggu final approval HR.',
            ]);
        }

        return DB::transaction(function () use ($hr, $request, $note): LeaveRequest {
            $request->loadMissing('leaveType');

            if ($request->leaveType->deduct_balance) {
                $year = $request->start_date->year;

                $balance = LeaveBalance::query()
                    ->where('employee_id', $request->employee_id)
                    ->where('leave_type_id', $request->leave_type_id)
                    ->where('year', $year)
                    ->lockForUpdate()
                    ->first();

                if (! $balance || $balance->available_days < $request->requested_days) {
                    throw ValidationException::withMessages([
                        'leave_request' => 'Saldo cuti tidak lagi mencukupi.',
                    ]);
                }

                $balance->increment('used_days', $request->requested_days);
            }

            LeaveApproval::create([
                'leave_request_id' => $request->id,
                'stage' => 'hr',
                'action' => 'approved',
                'approver_user_id' => $hr->id,
                'note' => $note,
                'acted_at' => now(),
            ]);

            $request->update(['status' => 'APPROVED']);

            return $request->fresh(['employee', 'leaveType', 'approvals']);
        });
    }

    public function reject(User $hr, LeaveRequest $request, ?string $note = null): LeaveRequest
    {
        if (! $hr->hasAnyRole('hr_admin', 'system_admin')) {
            abort(403);
        }

        if ($request->status !== 'PENDING_HR') {
            throw ValidationException::withMessages([
                'leave_request' => 'Pengajuan tidak lagi menunggu final approval HR.',
            ]);
        }

        return DB::transaction(function () use ($hr, $request, $note): LeaveRequest {
            LeaveApproval::create([
                'leave_request_id' => $request->id,
                'stage' => 'hr',
                'action' => 'rejected',
                'approver_user_id' => $hr->id,
                'note' => $note,
                'acted_at' => now(),
            ]);

            $request->update(['status' => 'REJECTED_HR']);

            return $request->fresh(['employee', 'leaveType', 'approvals']);
        });
    }
}
