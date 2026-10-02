<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Department;
use App\Models\LeaveRequest;
use App\Models\OvertimeRequest;
use App\Models\TemporaryPermission;
use App\Support\CsvExport;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        [$from, $to, $departmentId] = $this->filters($request);

        $attendance = $this->attendanceQuery($from, $to, $departmentId);
        $leave = $this->leaveQuery($from, $to, $departmentId);
        $overtime = $this->overtimeQuery($from, $to, $departmentId);
        $temporary = $this->temporaryPermissionQuery($from, $to, $departmentId);

        return view('hr.reports.index', [
            'from' => $from,
            'to' => $to,
            'departmentId' => $departmentId,
            'departments' => Department::query()->orderBy('name')->get(),
            'summary' => [
                'attendance_total' => (clone $attendance)->count(),
                'present' => (clone $attendance)->where('attendance_status', 'PRESENT')->count(),
                'late' => (clone $attendance)->where('attendance_status', 'LATE')->count(),
                'absent' => (clone $attendance)->where('attendance_status', 'ABSENT')->count(),
                'leave' => (clone $attendance)->where('attendance_status', 'LEAVE')->count(),
                'permit' => (clone $attendance)->where('attendance_status', 'PERMIT')->count(),
                'incomplete' => (clone $attendance)->where('attendance_status', 'INCOMPLETE')->count(),
                'leave_requests' => (clone $leave)->count(),
                'approved_overtime_minutes' => (int) (clone $overtime)
                    ->where('status', 'APPROVED')->sum('duration_minutes'),
                'temporary_permission_minutes' => (int) ceil(
                    ((int) (clone $temporary)
                        ->whereNotNull('duration_seconds')
                        ->sum('duration_seconds')) / 60
                ),
            ],
        ]);
    }

    public function attendanceCsv(Request $request): StreamedResponse
    {
        [$from, $to, $departmentId] = $this->filters($request);

        $rows = $this->attendanceQuery($from, $to, $departmentId)
            ->with(['employee.department', 'shiftSchedule.shift'])
            ->orderBy('work_date')
            ->orderBy('employee_id')
            ->cursor()
            ->map(fn (Attendance $attendance) => [
                $attendance->work_date->format('Y-m-d'),
                $attendance->employee->nip,
                $attendance->employee->name,
                $attendance->employee->department->name,
                $attendance->shiftSchedule->shift->name,
                $attendance->check_in_at?->timezone(config('app.timezone'))->format('Y-m-d H:i:s') ?? '',
                $attendance->check_out_at?->timezone(config('app.timezone'))->format('Y-m-d H:i:s') ?? '',
                $attendance->late_minutes ?? 0,
                $attendance->temporary_permission_minutes ?? 0,
                $attendance->attendance_status ?? '',
            ]);

        return CsvExport::download(
            "attendance-{$from}-{$to}.csv",
            ['Work Date','NIP','Employee','Department','Shift','Check In','Check Out','Late Minutes','Exit Permission Minutes','Status'],
            $rows
        );
    }

    public function leaveCsv(Request $request): StreamedResponse
    {
        [$from, $to, $departmentId] = $this->filters($request);

        $rows = $this->leaveQuery($from, $to, $departmentId)
            ->with(['employee.department', 'leaveType'])
            ->orderBy('start_date')
            ->orderBy('employee_id')
            ->cursor()
            ->map(fn (LeaveRequest $leave) => [
                $leave->employee->nip,
                $leave->employee->name,
                $leave->employee->department->name,
                $leave->leaveType->name,
                $leave->leaveType->category,
                $leave->start_date->format('Y-m-d'),
                $leave->end_date->format('Y-m-d'),
                $leave->requested_days,
                $leave->status,
                $leave->reason,
            ]);

        return CsvExport::download(
            "leave-permit-{$from}-{$to}.csv",
            ['NIP','Employee','Department','Type','Category','Start Date','End Date','Work Days','Status','Reason'],
            $rows
        );
    }

    public function temporaryPermissionCsv(Request $request): StreamedResponse
    {
        [$from, $to, $departmentId] = $this->filters($request);

        $rows = $this->temporaryPermissionQuery($from, $to, $departmentId)
            ->with(['employee.department', 'shiftSchedule.shift', 'approver'])
            ->orderBy('id')
            ->cursor()
            ->map(fn (TemporaryPermission $permission) => [
                $permission->shiftSchedule->work_date->format('Y-m-d'),
                $permission->employee->nip,
                $permission->employee->name,
                $permission->employee->department->name,
                $permission->shiftSchedule->shift->name,
                $permission->reason,
                $permission->status,
                $permission->out_at?->timezone(config('app.timezone'))->format('Y-m-d H:i:s') ?? '',
                $permission->returned_at?->timezone(config('app.timezone'))->format('Y-m-d H:i:s') ?? '',
                $permission->duration_minutes ?? '',
                $permission->approver?->name ?? '',
            ]);

        return CsvExport::download(
            "temporary-permission-{$from}-{$to}.csv",
            ['Work Date','NIP','Employee','Department','Shift','Reason','Status','Out At','Returned At','Duration Minutes','Approver'],
            $rows
        );
    }

    public function overtimeCsv(Request $request): StreamedResponse
    {
        [$from, $to, $departmentId] = $this->filters($request);

        $rows = $this->overtimeQuery($from, $to, $departmentId)
            ->with(['employee.department', 'shiftSchedule.shift'])
            ->orderBy('work_date')
            ->orderBy('employee_id')
            ->cursor()
            ->map(fn (OvertimeRequest $overtime) => [
                $overtime->work_date->format('Y-m-d'),
                $overtime->employee->nip,
                $overtime->employee->name,
                $overtime->employee->department->name,
                $overtime->shiftSchedule->shift->name,
                $overtime->start_at->timezone(config('app.timezone'))->format('Y-m-d H:i:s'),
                $overtime->end_at->timezone(config('app.timezone'))->format('Y-m-d H:i:s'),
                $overtime->duration_minutes,
                $overtime->status,
                $overtime->reason,
            ]);

        return CsvExport::download(
            "overtime-{$from}-{$to}.csv",
            ['Work Date','NIP','Employee','Department','Shift','Start At','End At','Duration Minutes','Status','Reason'],
            $rows
        );
    }

    private function filters(Request $request): array
    {
        $data = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
        ]);

        $timezone = config('app.timezone');
        $from = $data['from'] ?? now($timezone)->startOfMonth()->toDateString();
        $to = $data['to'] ?? now($timezone)->endOfMonth()->toDateString();

        return [$from, $to, isset($data['department_id']) ? (int) $data['department_id'] : null];
    }

    private function attendanceQuery(string $from, string $to, ?int $departmentId): Builder
    {
        return Attendance::query()
            ->whereDate('work_date', '>=', $from)
            ->whereDate('work_date', '<=', $to)
            ->when($departmentId, fn (Builder $query) => $query
                ->whereHas('employee', fn (Builder $employee) => $employee
                    ->where('department_id', $departmentId)));
    }

    private function leaveQuery(string $from, string $to, ?int $departmentId): Builder
    {
        return LeaveRequest::query()
            ->whereDate('start_date', '<=', $to)
            ->whereDate('end_date', '>=', $from)
            ->when($departmentId, fn (Builder $query) => $query
                ->whereHas('employee', fn (Builder $employee) => $employee
                    ->where('department_id', $departmentId)));
    }

    private function overtimeQuery(string $from, string $to, ?int $departmentId): Builder
    {
        return OvertimeRequest::query()
            ->whereDate('work_date', '>=', $from)
            ->whereDate('work_date', '<=', $to)
            ->when($departmentId, fn (Builder $query) => $query
                ->whereHas('employee', fn (Builder $employee) => $employee
                    ->where('department_id', $departmentId)));
    }

    private function temporaryPermissionQuery(string $from, string $to, ?int $departmentId): Builder
    {
        return TemporaryPermission::query()
            ->whereHas('shiftSchedule', fn (Builder $schedule) => $schedule
                ->whereDate('work_date', '>=', $from)
                ->whereDate('work_date', '<=', $to))
            ->when($departmentId, fn (Builder $query) => $query
                ->whereHas('employee', fn (Builder $employee) => $employee
                    ->where('department_id', $departmentId)));
    }
}
