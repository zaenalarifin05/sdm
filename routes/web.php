<?php

use App\Http\Controllers\AttendanceKioskController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Finance\EmployeeCompensationController;
use App\Http\Controllers\Finance\PayrollDraftController;
use App\Http\Controllers\Finance\PayrollEntryController;
use App\Http\Controllers\Finance\PayrollFinalizeController;
use App\Http\Controllers\Finance\PayrollItemController;
use App\Http\Controllers\Finance\PayrollPeriodController;
use App\Http\Controllers\Finance\PayrollPolicyPreviewController;
use App\Http\Controllers\Hr\AttendanceMonitorController;
use App\Http\Controllers\Hr\DepartmentController;
use App\Http\Controllers\Hr\EmployeeController;
use App\Http\Controllers\Hr\HolidayController;
use App\Http\Controllers\Hr\LeaveApprovalController as HrLeaveApprovalController;
use App\Http\Controllers\Hr\LeaveBalanceController;
use App\Http\Controllers\Hr\LeaveTypeController;
use App\Http\Controllers\Hr\OvertimeApprovalController as HrOvertimeApprovalController;
use App\Http\Controllers\Hr\ShiftController;
use App\Http\Controllers\Hr\ShiftScheduleController;
use App\Http\Controllers\Manager\LeaveApprovalController as ManagerLeaveApprovalController;
use App\Http\Controllers\Manager\OvertimeApprovalController as ManagerOvertimeApprovalController;
use App\Http\Controllers\Manager\TemporaryPermissionApprovalController;
use App\Http\Controllers\MyAttendanceController;
use App\Http\Controllers\MyLeaveController;
use App\Http\Controllers\MyOvertimeController;
use App\Http\Controllers\MyTemporaryPermissionController;
use Illuminate\Support\Facades\Route;

Route::get('/attendance', [AttendanceKioskController::class, 'create'])->name('attendance.create');
Route::post('/attendance', [AttendanceKioskController::class, 'store'])
    ->middleware('throttle:attendance-kiosk')
    ->name('attendance.store');

Route::get('/', fn () => redirect()->route('dashboard'));

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/my/attendance', MyAttendanceController::class)->name('my.attendance');

    Route::get('/my/leave', [MyLeaveController::class, 'index'])->name('my.leave.index');
    Route::get('/my/leave/create', [MyLeaveController::class, 'create'])->name('my.leave.create');
    Route::post('/my/leave', [MyLeaveController::class, 'store'])->name('my.leave.store');

    Route::get('/my/overtime', [MyOvertimeController::class, 'index'])->name('my.overtime.index');
    Route::get('/my/overtime/create', [MyOvertimeController::class, 'create'])->name('my.overtime.create');
    Route::post('/my/overtime', [MyOvertimeController::class, 'store'])->name('my.overtime.store');

    Route::get('/my/temporary-permissions', [MyTemporaryPermissionController::class, 'index'])->name('my.temporary-permissions.index');
    Route::get('/my/temporary-permissions/create', [MyTemporaryPermissionController::class, 'create'])->name('my.temporary-permissions.create');
    Route::post('/my/temporary-permissions', [MyTemporaryPermissionController::class, 'store'])->name('my.temporary-permissions.store');
    Route::post('/my/temporary-permissions/{temporaryPermission}/start', [MyTemporaryPermissionController::class, 'start'])->name('my.temporary-permissions.start');
    Route::post('/my/temporary-permissions/{temporaryPermission}/return', [MyTemporaryPermissionController::class, 'return'])->name('my.temporary-permissions.return');

    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::prefix('manager')->name('manager.')->middleware('role:manager')->group(function () {
        Route::get('leave', [ManagerLeaveApprovalController::class, 'index'])->name('leave.index');
        Route::post('leave/{leaveRequest}/approve', [ManagerLeaveApprovalController::class, 'approve'])->name('leave.approve');
        Route::post('leave/{leaveRequest}/reject', [ManagerLeaveApprovalController::class, 'reject'])->name('leave.reject');

        Route::get('overtime', [ManagerOvertimeApprovalController::class, 'index'])->name('overtime.index');
        Route::post('overtime/{overtimeRequest}/approve', [ManagerOvertimeApprovalController::class, 'approve'])->name('overtime.approve');
        Route::post('overtime/{overtimeRequest}/reject', [ManagerOvertimeApprovalController::class, 'reject'])->name('overtime.reject');

        Route::get('temporary-permissions', [TemporaryPermissionApprovalController::class, 'index'])->name('temporary-permissions.index');
        Route::post('temporary-permissions/{temporaryPermission}/approve', [TemporaryPermissionApprovalController::class, 'approve'])->name('temporary-permissions.approve');
        Route::post('temporary-permissions/{temporaryPermission}/reject', [TemporaryPermissionApprovalController::class, 'reject'])->name('temporary-permissions.reject');
    });

    Route::prefix('hr')->name('hr.')->middleware('role:hr_admin,system_admin')->group(function () {
        Route::get('attendance', [AttendanceMonitorController::class, 'index'])->name('attendance.index');
        Route::post('attendance/process', [AttendanceMonitorController::class, 'process'])->name('attendance.process');

        Route::get('leave', [HrLeaveApprovalController::class, 'index'])->name('leave.index');
        Route::post('leave/{leaveRequest}/approve', [HrLeaveApprovalController::class, 'approve'])->name('leave.approve');
        Route::post('leave/{leaveRequest}/reject', [HrLeaveApprovalController::class, 'reject'])->name('leave.reject');

        Route::get('overtime', [HrOvertimeApprovalController::class, 'index'])->name('overtime.index');
        Route::post('overtime/{overtimeRequest}/approve', [HrOvertimeApprovalController::class, 'approve'])->name('overtime.approve');
        Route::post('overtime/{overtimeRequest}/reject', [HrOvertimeApprovalController::class, 'reject'])->name('overtime.reject');

        Route::get('leave-types', [LeaveTypeController::class, 'index'])->name('leave-types.index');
        Route::post('leave-types', [LeaveTypeController::class, 'store'])->name('leave-types.store');
        Route::put('leave-types/{leaveType}', [LeaveTypeController::class, 'update'])->name('leave-types.update');

        Route::get('leave-balances', [LeaveBalanceController::class, 'index'])->name('leave-balances.index');
        Route::post('leave-balances', [LeaveBalanceController::class, 'store'])->name('leave-balances.store');

        Route::resource('holidays', HolidayController::class)->except('show');
        Route::resource('departments', DepartmentController::class)->except('show');
        Route::resource('employees', EmployeeController::class)->except('show');
        Route::resource('shifts', ShiftController::class)->except('show');
        Route::resource('schedules', ShiftScheduleController::class)->except('show');
    });

    Route::prefix('finance')->name('finance.')->middleware('role:finance')->group(function () {
        Route::get('compensations', [EmployeeCompensationController::class, 'index'])->name('compensations.index');
        Route::post('compensations', [EmployeeCompensationController::class, 'store'])->name('compensations.store');

        Route::get('payroll', [PayrollPeriodController::class, 'index'])->name('payroll.index');
        Route::post('payroll', [PayrollPeriodController::class, 'store'])->name('payroll.store');
        Route::get('payroll/{payrollPeriod}', [PayrollPeriodController::class, 'show'])->name('payroll.show');
        Route::post('payroll/{payrollPeriod}/generate', [PayrollDraftController::class, 'generate'])->name('payroll.generate');
        Route::post('payroll/{payrollPeriod}/apply-policies', [PayrollPolicyPreviewController::class, 'store'])->name('payroll.apply-policies');
        Route::post('payroll/{payrollPeriod}/finalize', [PayrollFinalizeController::class, 'store'])->name('payroll.finalize');

        Route::get('payroll-entry/{payroll}', [PayrollEntryController::class, 'show'])->name('payroll-entry.show');
        Route::post('payroll-entry/{payroll}/items', [PayrollItemController::class, 'store'])->name('payroll-items.store');
        Route::delete('payroll-entry/{payroll}/items/{payrollItem}', [PayrollItemController::class, 'destroy'])->name('payroll-items.destroy');
    });
});
