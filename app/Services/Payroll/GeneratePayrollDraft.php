<?php

namespace App\Services\Payroll;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\EmployeeCompensation;
use App\Models\OvertimeRequest;
use App\Models\Payroll;
use App\Models\PayrollPeriod;
use App\Models\ShiftSchedule;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GeneratePayrollDraft
{
    public function __construct(
        private readonly RecalculatePayrollTotals $recalculate,
    ) {
    }

    public function execute(PayrollPeriod $period, User $actor): Collection
    {
        if (! $actor->hasAnyRole('finance')) {
            abort(403);
        }

        if ($period->status !== 'DRAFT') {
            throw ValidationException::withMessages([
                'payroll_period' => 'Payroll period tidak lagi berstatus DRAFT.',
            ]);
        }

        $employees = Employee::query()
            ->where('is_active', true)
            ->where('employment_status', 'active')
            ->when($period->period_end, fn ($query) => $query
                ->where(function ($inner) use ($period) {
                    $inner->whereNull('join_date')
                        ->orWhereDate('join_date', '<=', $period->period_end->format('Y-m-d'));
                }))
            ->orderBy('id')
            ->get();

        $missingCompensation = [];
        $unprocessedAttendance = [];

        foreach ($employees as $employee) {
            $compensation = $this->effectiveCompensation($employee, $period);

            if (! $compensation) {
                $missingCompensation[] = $employee->nip;
                continue;
            }

            $scheduledDays = ShiftSchedule::query()
                ->where('employee_id', $employee->id)
                ->where('status', 'scheduled')
                ->whereDate('work_date', '>=', $period->period_start->format('Y-m-d'))
                ->whereDate('work_date', '<=', $period->period_end->format('Y-m-d'))
                ->count();

            $processedAttendance = Attendance::query()
                ->where('employee_id', $employee->id)
                ->whereDate('work_date', '>=', $period->period_start->format('Y-m-d'))
                ->whereDate('work_date', '<=', $period->period_end->format('Y-m-d'))
                ->whereNotNull('attendance_status')
                ->count();

            if ($processedAttendance < $scheduledDays) {
                $unprocessedAttendance[] = $employee->nip;
            }
        }

        if ($missingCompensation !== []) {
            throw ValidationException::withMessages([
                'payroll_period' => 'Compensation efektif belum tersedia untuk NIP: '.implode(', ', $missingCompensation),
            ]);
        }

        if ($unprocessedAttendance !== []) {
            throw ValidationException::withMessages([
                'payroll_period' => 'Attendance belum lengkap diproses untuk NIP: '.implode(', ', $unprocessedAttendance),
            ]);
        }

        return DB::transaction(function () use ($employees, $period, $actor): Collection {
            return $employees->map(function (Employee $employee) use ($period, $actor): Payroll {
                $compensation = $this->effectiveCompensation($employee, $period);

                $scheduledDays = ShiftSchedule::query()
                    ->where('employee_id', $employee->id)
                    ->where('status', 'scheduled')
                    ->whereDate('work_date', '>=', $period->period_start->format('Y-m-d'))
                    ->whereDate('work_date', '<=', $period->period_end->format('Y-m-d'))
                    ->count();

                $attendance = Attendance::query()
                    ->where('employee_id', $employee->id)
                    ->whereDate('work_date', '>=', $period->period_start->format('Y-m-d'))
                    ->whereDate('work_date', '<=', $period->period_end->format('Y-m-d'));

                $presentDays = (clone $attendance)->where('attendance_status', 'PRESENT')->count();
                $lateDays = (clone $attendance)->where('attendance_status', 'LATE')->count();
                $lateMinutes = (int) (clone $attendance)->sum('late_minutes');
                $absentDays = (clone $attendance)->where('attendance_status', 'ABSENT')->count();
                $leaveDays = (clone $attendance)->where('attendance_status', 'LEAVE')->count();
                $permitDays = (clone $attendance)->where('attendance_status', 'PERMIT')->count();
                $incompleteDays = (clone $attendance)->where('attendance_status', 'INCOMPLETE')->count();

                $approvedOvertimeMinutes = (int) OvertimeRequest::query()
                    ->where('employee_id', $employee->id)
                    ->where('status', 'APPROVED')
                    ->whereDate('work_date', '>=', $period->period_start->format('Y-m-d'))
                    ->whereDate('work_date', '<=', $period->period_end->format('Y-m-d'))
                    ->sum('duration_minutes');

                $payroll = Payroll::query()->updateOrCreate(
                    [
                        'payroll_period_id' => $period->id,
                        'employee_id' => $employee->id,
                    ],
                    [
                        'employee_compensation_id' => $compensation->id,
                        'base_salary_snapshot' => $compensation->base_salary,
                        'currency' => $compensation->currency,
                        'scheduled_days' => $scheduledDays,
                        'present_days' => $presentDays,
                        'late_days' => $lateDays,
                        'late_minutes' => $lateMinutes,
                        'absent_days' => $absentDays,
                        'leave_days' => $leaveDays,
                        'permit_days' => $permitDays,
                        'incomplete_days' => $incompleteDays,
                        'approved_overtime_minutes' => $approvedOvertimeMinutes,
                        'status' => 'DRAFT',
                        'generated_at' => now(),
                        'generated_by' => $actor->id,
                    ]
                );

                return $this->recalculate->execute($payroll);
            });
        });
    }

    private function effectiveCompensation(
        Employee $employee,
        PayrollPeriod $period
    ): ?EmployeeCompensation {
        return EmployeeCompensation::query()
            ->where('employee_id', $employee->id)
            ->whereDate('effective_from', '<=', $period->period_end->format('Y-m-d'))
            ->orderByDesc('effective_from')
            ->orderByDesc('id')
            ->first();
    }
}
