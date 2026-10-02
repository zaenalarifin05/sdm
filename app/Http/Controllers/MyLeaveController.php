<?php

namespace App\Http\Controllers;

use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Services\Leave\SubmitLeaveRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MyLeaveController extends Controller
{
    public function index(Request $request): View
    {
        $employee = $request->user()->employee;
        abort_unless($employee, 404);

        return view('leave.my.index', [
            'employee' => $employee,
            'requests' => LeaveRequest::query()
                ->with('leaveType')
                ->where('employee_id', $employee->id)
                ->latest()
                ->paginate(20),
            'balances' => LeaveBalance::query()
                ->with('leaveType')
                ->where('employee_id', $employee->id)
                ->where('year', now()->year)
                ->get(),
        ]);
    }

    public function create(Request $request): View
    {
        $employee = $request->user()->employee;
        abort_unless($employee, 404);

        return view('leave.my.create', [
            'leaveTypes' => LeaveType::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, SubmitLeaveRequest $submit): RedirectResponse
    {
        $employee = $request->user()->employee;
        abort_unless($employee, 404);

        $data = $request->validate([
            'leave_type_id' => ['required', 'integer', 'exists:leave_types,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date'],
            'reason' => ['required', 'string', 'max:2000'],
        ]);

        $submit->execute(
            $employee,
            (int) $data['leave_type_id'],
            $data['start_date'],
            $data['end_date'],
            $data['reason'],
        );

        return redirect()->route('my.leave.index')
            ->with('status', 'Pengajuan berhasil dikirim ke atasan departemen.');
    }
}
