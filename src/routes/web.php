<?php

use App\Http\Controllers\Admin\AttendanceController as AdminAttendanceController;
use App\Http\Controllers\Admin\LoginController;
use App\Http\Controllers\AttendanceController as UserAttendanceController;
use Illuminate\Support\Facades\Route;

// ユーザー認証
Route::middleware(['auth:web'])->group(function () {
    Route::get('/attendance', [UserAttendanceController::class, 'create'])->name('attendance.create');
});

// 管理者認証
Route::prefix('admin')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('admin.loginForm');
    Route::post('/login', [LoginController::class, 'login'])->name('admin.login');
    Route::post('/logout', [LoginController::class, 'logout'])->name('admin.logout');

    Route::middleware(['auth:admin'])->group(function () {
        Route::get('/attendance/list', [AdminAttendanceController::class, 'index']);
    });
});
