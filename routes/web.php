<?php

use App\Http\Controllers\AttendanceKioskController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Hr\AttendanceMonitorController;
use App\Http\Controllers\Hr\DepartmentController;
use App\Http\Controllers\Hr\EmployeeController;
use App\Http\Controllers\Hr\HolidayController;
use App\Http\Controllers\Hr\LeaveApprovalController as HrLeaveApprovalController;
use App\Http\Controllers\Hr\LeaveBalanceController;
use App\Http\Controllers\Hr\LeaveTypeController;
use App\Http\Controllers\Hr\ShiftController;
use App\Http\Controllers\Hr\ShiftScheduleController;
use App\Http\Controllers\Manager\LeaveApprovalController as ManagerLeaveApprovalController;
use App\Http\Controllers\MyAttendanceController;
use App\Http\Controllers\MyLeaveController;
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

    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::prefix('manager')->name('manager.')->middleware('role:manager')->group(function () {
        Route::get('leave', [ManagerLeaveApprovalController::class, 'index'])->name('leave.index');
        Route::post('leave/{leaveRequest}/approve', [ManagerLeaveApprovalController::class, 'approve'])->name('leave.approve');
        Route::post('leave/{leaveRequest}/reject', [ManagerLeaveApprovalController::class, 'reject'])->name('leave.reject');
    });

    Route::prefix('hr')->name('hr.')->middleware('role:hr_admin,system_admin')->group(function () {
        Route::get('attendance', [AttendanceMonitorController::class, 'index'])->name('attendance.index');
        Route::post('attendance/process', [AttendanceMonitorController::class, 'process'])->name('attendance.process');

        Route::get('leave', [HrLeaveApprovalController::class, 'index'])->name('leave.index');
        Route::post('leave/{leaveRequest}/approve', [HrLeaveApprovalController::class, 'approve'])->name('leave.approve');
        Route::post('leave/{leaveRequest}/reject', [HrLeaveApprovalController::class, 'reject'])->name('leave.reject');

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
});
