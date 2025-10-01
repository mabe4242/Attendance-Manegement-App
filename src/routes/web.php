<?php

use App\Http\Controllers\Admin\AttendanceController as AdminAttendanceController;
use App\Http\Controllers\Admin\AttendanceRequestController as AdminAttendanceRequestController;
use App\Http\Controllers\Admin\LoginController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\AttendanceController as UserAttendanceController;
use App\Http\Controllers\AttendanceRequestController as UserAttendanceRequestController;
use App\Http\Controllers\BreakController;
use Illuminate\Support\Facades\Route;

// ユーザー認証
Route::middleware(['auth:web'])->group(function () {
    Route::get('/attendance', [UserAttendanceController::class, 'create'])->name('attendance.create');
    Route::post('/attendance', [UserAttendanceController::class, 'store'])->name('attendance.store');
    Route::post('/attendance/checkout', [UserAttendanceController::class, 'checkout'])->name('attendance.checkout');
    Route::get('/attendance/list', [UserAttendanceController::class, 'index'])->name('attendance.index');
    Route::post('/attendance/{id}/break/start', [BreakController::class, 'start'])->name('break.start');
    Route::post('/attendance/{id}/break/end', [BreakController::class, 'end'])->name('break.end');
    Route::get('/attendance/detail/{id}', [UserAttendanceRequestController::class, 'show'])->name('attendance.detail');
    Route::get('/attendance/detail_or_create/{date}', [UserAttendanceRequestController::class, 'detailOrCreate'])->name('attendance.detail_or_create');
    Route::post('/attendance/detail/{id}', [UserAttendanceRequestController::class, 'store'])->name('attendance_request.store');
    Route::get('/stamp_correction_request/list', [UserAttendanceRequestController::class, 'index'])->name('attendance_requests.index');
});

// 管理者認証
Route::prefix('admin')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('admin.loginForm');
    Route::post('/login', [LoginController::class, 'login'])->name('admin.login');
    Route::post('/logout', [LoginController::class, 'logout'])->name('admin.logout');

    Route::middleware(['auth:admin'])->group(function () {
        Route::get('/attendance/list', [AdminAttendanceController::class, 'index'])->name('admin.attendance.index');
        Route::get('/staff/list', [StaffController::class, 'index'])->name('admin.staff.index');
        Route::get('/stamp_correction_request/list', [AdminAttendanceRequestController::class, 'index'])->name('admin.attendance_requests.index');
        Route::get('/attendance/staff/{id}', [AdminAttendanceController::class, 'staffAttendances'])->name('admin.staff_attendance');
        Route::get('/attendance/{user}/detail_or_create/{date}', [AdminAttendanceController::class, 'detailOrCreate'])->name('admin.detail_or_create');
        Route::get('/attendance/{id}', [AdminAttendanceController::class, 'show'])->name('admin.attendance.show');
        Route::put('/attendance/{id}', [AdminAttendanceController::class, 'update'])->name('admin.attendance.update');
    });
});
