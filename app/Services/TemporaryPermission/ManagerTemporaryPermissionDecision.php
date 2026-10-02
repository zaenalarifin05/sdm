<?php

namespace App\Services\TemporaryPermission;

use App\Models\TemporaryPermission;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ManagerTemporaryPermissionDecision
{
    public function approve(User $manager, TemporaryPermission $permission, ?string $note = null): TemporaryPermission
    {
        return $this->decide($manager, $permission, 'approved', $note);
    }

    public function reject(User $manager, TemporaryPermission $permission, ?string $note = null): TemporaryPermission
    {
        return $this->decide($manager, $permission, 'rejected', $note);
    }

    private function decide(
        User $manager,
        TemporaryPermission $permission,
        string $action,
        ?string $note
    ): TemporaryPermission {
        $manager->loadMissing('employee');
        $permission->loadMissing('employee.department');

        if (! $manager->employee
            || $permission->employee->department?->manager_employee_id !== $manager->employee->id) {
            abort(403);
        }

        if ($permission->status !== 'PENDING_MANAGER') {
            throw ValidationException::withMessages([
                'temporary_permission' => 'Izin keluar tidak lagi menunggu approval atasan.',
            ]);
        }

        return DB::transaction(function () use ($manager, $permission, $action, $note): TemporaryPermission {
            $permission->update([
                'status' => $action === 'approved' ? 'APPROVED' : 'REJECTED',
                'approved_by_user_id' => $manager->id,
                'approval_note' => $note,
                'approved_at' => now(),
            ]);

            return $permission->fresh(['employee', 'shiftSchedule.shift', 'approver']);
        });
    }
}
