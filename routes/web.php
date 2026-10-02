<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Hr\DepartmentController;
use App\Http\Controllers\Hr\EmployeeController;
use App\Http\Controllers\Hr\ShiftController;
use App\Http\Controllers\Hr\ShiftScheduleController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'));

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::prefix('hr')->name('hr.')->middleware('role:hr_admin,system_admin')->group(function () {
        Route::resource('departments', DepartmentController::class)->except('show');
        Route::resource('employees', EmployeeController::class)->except('show');
        Route::resource('shifts', ShiftController::class)->except('show');
        Route::resource('schedules', ShiftScheduleController::class)->except('show');
    });
});
