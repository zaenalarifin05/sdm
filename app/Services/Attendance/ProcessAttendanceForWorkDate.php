<?php

namespace App\Services\Attendance;

use App\Models\Attendance;
use App\Models\ShiftSchedule;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProcessAttendanceForWorkDate
{
    public function __construct(
        private readonly AttendanceStatusPolicy $statusPolicy,
    ) {
    }

    public function execute(string $workDate, ?CarbonImmutable $asOf = null): Collection
    {
        $timezone = (string) config('app.timezone', 'Asia/Jakarta');
        $asOf = ($asOf ?? CarbonImmutable::now($timezone))->setTimezone($timezone);

        return DB::transaction(function () use ($workDate, $asOf, $timezone): Collection {
            $schedules = ShiftSchedule::query()
                ->with(['shift', 'employee'])
                ->whereDate('work_date', $workDate)
                ->where('status', 'scheduled')
                ->lockForUpdate()
                ->get();

            return $schedules->map(function (ShiftSchedule $schedule) use ($asOf, $timezone): array {
                [$scheduledStart, $scheduledEnd] = $this->scheduledWindow($schedule, $timezone);

                if ($asOf->lessThan($scheduledEnd)) {
                    return [
                        'schedule_id' => $schedule->id,
                        'processed' => false,
                        'status' => null,
                    ];
                }

                $attendance = Attendance::query()
                    ->where('shift_schedule_id', $schedule->id)
                    ->lockForUpdate()
                    ->first();

                if (! $attendance) {
                    $attendance = Attendance::create([
                        'employee_id' => $schedule->employee_id,
                        'shift_schedule_id' => $schedule->id,
                        'work_date' => $schedule->work_date,
                        'scheduled_start_at' => $scheduledStart,
                        'scheduled_end_at' => $scheduledEnd,
                        'state' => 'absent',
                        'attendance_status' => 'ABSENT',
                    ]);
                } elseif ($attendance->check_out_at) {
                    $attendance->update([
                        'state' => 'completed',
                        'late_minutes' => $this->statusPolicy->lateMinutes($attendance),
                        'attendance_status' => $this->statusPolicy->completedStatus($attendance),
                    ]);
                } else {
                    $attendance->update([
                        'state' => 'incomplete',
                        'late_minutes' => $this->statusPolicy->lateMinutes($attendance),
                        'attendance_status' => 'INCOMPLETE',
                    ]);
                }

                return [
                    'schedule_id' => $schedule->id,
                    'processed' => true,
                    'status' => $attendance->attendance_status,
                ];
            });
        });
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
