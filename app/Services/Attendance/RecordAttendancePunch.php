<?php

namespace App\Services\Attendance;

use App\Data\AttendancePunchResult;
use App\Exceptions\AttendancePunchException;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\ShiftSchedule;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class RecordAttendancePunch
{
    public function __construct(
        private readonly AttendanceStatusPolicy $statusPolicy,
    ) {
    }

    public function execute(string $nip, string $pin, ?CarbonImmutable $now = null): AttendancePunchResult
    {
        $timezone = (string) config('app.timezone', 'Asia/Jakarta');
        $now = ($now ?? CarbonImmutable::now($timezone))->setTimezone($timezone);
        $nip = trim($nip);

        return DB::transaction(function () use ($nip, $pin, $now, $timezone): AttendancePunchResult {
            $employee = Employee::query()
                ->where('nip', $nip)
                ->where('is_active', true)
                ->where('employment_status', 'active')
                ->lockForUpdate()
                ->first();

            if (! $employee || ! Hash::check($pin, $employee->attendance_pin_hash)) {
                throw AttendancePunchException::invalidCredentials();
            }

            $openAttendance = Attendance::query()
                ->where('employee_id', $employee->id)
                ->whereNotNull('check_in_at')
                ->whereNull('check_out_at')
                ->latest('check_in_at')
                ->lockForUpdate()
                ->first();

            if ($openAttendance) {
                $openAttendance->check_out_at = $now;
                $openAttendance->state = 'completed';
                $openAttendance->late_minutes = $this->statusPolicy->lateMinutes($openAttendance);
                $openAttendance->attendance_status = $this->statusPolicy->completedStatus($openAttendance);
                $openAttendance->save();

                return new AttendancePunchResult(
                    'check_out',
                    $openAttendance->fresh(['employee', 'shiftSchedule.shift'])
                );
            }

            $schedule = $this->findRelevantSchedule($employee, $now);

            if (! $schedule) {
                throw AttendancePunchException::noSchedule();
            }

            $schedule = ShiftSchedule::query()
                ->with('shift')
                ->whereKey($schedule->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (Attendance::query()->where('shift_schedule_id', $schedule->id)->exists()) {
                throw AttendancePunchException::alreadyCompleted();
            }

            [$scheduledStart, $scheduledEnd] = $this->scheduledWindow($schedule, $timezone);

            $attendance = Attendance::create([
                'employee_id' => $employee->id,
                'shift_schedule_id' => $schedule->id,
                'work_date' => $schedule->work_date,
                'scheduled_start_at' => $scheduledStart,
                'scheduled_end_at' => $scheduledEnd,
                'check_in_at' => $now,
                'state' => 'checked_in',
                'late_minutes' => max(0, intdiv($now->getTimestamp() - $scheduledStart->getTimestamp(), 60)),
            ]);

            return new AttendancePunchResult(
                'check_in',
                $attendance->fresh(['employee', 'shiftSchedule.shift'])
            );
        });
    }

    private function findRelevantSchedule(Employee $employee, CarbonImmutable $now): ?ShiftSchedule
    {
        $today = $now->toDateString();
        $previousDate = $now->subDay()->toDateString();

        $previousNightSchedule = ShiftSchedule::query()
            ->with('shift')
            ->where('employee_id', $employee->id)
            ->whereDate('work_date', $previousDate)
            ->where('status', 'scheduled')
            ->whereHas('shift', fn ($query) => $query
                ->where('crosses_midnight', true)
                ->where('is_active', true))
            ->first();

        if ($previousNightSchedule
            && ! Attendance::query()->where('shift_schedule_id', $previousNightSchedule->id)->exists()) {
            [, $previousEnd] = $this->scheduledWindow(
                $previousNightSchedule,
                (string) config('app.timezone', 'Asia/Jakarta')
            );

            if ($now->lessThanOrEqualTo($previousEnd)) {
                return $previousNightSchedule;
            }
        }

        return ShiftSchedule::query()
            ->with('shift')
            ->where('employee_id', $employee->id)
            ->whereDate('work_date', $today)
            ->where('status', 'scheduled')
            ->whereHas('shift', fn ($query) => $query->where('is_active', true))
            ->first();
    }

    private function scheduledWindow(ShiftSchedule $schedule, string $timezone): array
    {
        $workDate = CarbonImmutable::parse($schedule->work_date->format('Y-m-d'), $timezone);
        $start = CarbonImmutable::parse(
            $workDate->format('Y-m-d').' '.$schedule->shift->start_time,
            $timezone
        );

        $endDate = $schedule->shift->crosses_midnight ? $workDate->addDay() : $workDate;
        $end = CarbonImmutable::parse(
            $endDate->format('Y-m-d').' '.$schedule->shift->end_time,
            $timezone
        );

        return [$start, $end];
    }
}
