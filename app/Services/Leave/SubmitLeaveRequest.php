<?php

namespace App\Services\Leave;

use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveRequestDay;
use App\Models\LeaveType;
use App\Models\ShiftSchedule;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SubmitLeaveRequest
{
    public function execute(
        Employee $employee,
        int $leaveTypeId,
        string $startDate,
        string $endDate,
        string $reason,
    ): LeaveRequest {
        $start = CarbonImmutable::parse($startDate);
        $end = CarbonImmutable::parse($endDate);

        if ($end->lessThan($start)) {
            throw ValidationException::withMessages([
                'end_date' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
            ]);
        }

        if ($start->year !== $end->year) {
            throw ValidationException::withMessages([
                'end_date' => 'Pengajuan belum dapat melewati pergantian tahun.',
            ]);
        }

        $leaveType = LeaveType::query()
            ->whereKey($leaveTypeId)
            ->where('is_active', true)
            ->firstOrFail();

        $employee->loadMissing(['department.manager.user']);

        if (! $employee->department?->manager || ! $employee->department->manager->user?->is_active) {
            throw ValidationException::withMessages([
                'leave_type_id' => 'Atasan departemen belum dikonfigurasi.',
            ]);
        }

        $hasOverlap = LeaveRequest::query()
            ->where('employee_id', $employee->id)
            ->whereIn('status', ['PENDING_MANAGER', 'PENDING_HR', 'APPROVED'])
            ->whereDate('start_date', '<=', $end->toDateString())
            ->whereDate('end_date', '>=', $start->toDateString())
            ->exists();

        if ($hasOverlap) {
            throw ValidationException::withMessages([
                'start_date' => 'Sudah ada pengajuan aktif pada rentang tanggal tersebut.',
            ]);
        }

        $schedules = ShiftSchedule::query()
            ->where('employee_id', $employee->id)
            ->where('status', 'scheduled')
            ->whereDate('work_date', '>=', $start->toDateString())
            ->whereDate('work_date', '<=', $end->toDateString())
            ->orderBy('work_date')
            ->get();

        $requestedDays = $schedules->count();

        if ($requestedDays < 1) {
            throw ValidationException::withMessages([
                'start_date' => 'Tidak ada jadwal kerja aktif pada rentang pengajuan.',
            ]);
        }

        if ($leaveType->deduct_balance) {
            $balance = LeaveBalance::query()
                ->where('employee_id', $employee->id)
                ->where('leave_type_id', $leaveType->id)
                ->where('year', $start->year)
                ->first();

            if (! $balance || $balance->available_days < $requestedDays) {
                throw ValidationException::withMessages([
                    'leave_type_id' => 'Saldo cuti tidak mencukupi.',
                ]);
            }
        }

        return DB::transaction(function () use (
            $employee,
            $leaveType,
            $start,
            $end,
            $requestedDays,
            $reason,
            $schedules
        ): LeaveRequest {
            $request = LeaveRequest::create([
                'employee_id' => $employee->id,
                'leave_type_id' => $leaveType->id,
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'requested_days' => $requestedDays,
                'reason' => trim($reason),
                'status' => 'PENDING_MANAGER',
            ]);

            foreach ($schedules as $schedule) {
                LeaveRequestDay::create([
                    'leave_request_id' => $request->id,
                    'shift_schedule_id' => $schedule->id,
                    'work_date' => $schedule->work_date,
                ]);
            }

            return $request->fresh(['leaveType', 'days']);
        });
    }
}
