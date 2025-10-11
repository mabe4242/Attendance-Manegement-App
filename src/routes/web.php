<?php

use App\Http\Controllers\Admin\AttendanceController as AdminAttendanceController;
use App\Http\Controllers\Admin\AttendanceRequestController as AdminAttendanceRequestController;
use App\Http\Controllers\Admin\LoginController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\AttendanceController as UserAttendanceController;
use App\Http\Controllers\AttendanceRequestController as UserAttendanceRequestController;
use App\Http\Controllers\BreakController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// ユーザー認証
Route::middleware(['auth:web', 'verified'])->group(function () {
    Route::get('/attendance', [UserAttendanceController::class, 'create'])->name('attendance.create');
    Route::post('/attendance', [UserAttendanceController::class, 'store'])->name('attendance.store');
    Route::post('/attendance/checkout', [UserAttendanceController::class, 'checkout'])->name('attendance.checkout');
    Route::get('/attendance/list', [UserAttendanceController::class, 'index'])->name('attendance.index');
    Route::post('/attendance/{id}/break/start', [BreakController::class, 'start'])->name('break.start');
    Route::post('/attendance/{id}/break/end', [BreakController::class, 'end'])->name('break.end');
    Route::get('/attendance/detail/{id}', [UserAttendanceRequestController::class, 'show'])->name('attendance.detail');
    Route::get('/attendance/detail_or_create/{date}', [UserAttendanceRequestController::class, 'detailOrCreate'])->name('attendance.detail_or_create');
    Route::post('/attendance/detail/{id}', [UserAttendanceRequestController::class, 'store'])->name('attendance_request.store');
});

// 管理者認証
Route::prefix('admin')->group(function () {
    Route::middleware('guest:admin')->group(function () {
        Route::get('/login', [LoginController::class, 'create'])->name('admin.loginForm');
        Route::post('/login', [LoginController::class, 'login'])->name('admin.login');
    });
    Route::middleware(['auth:admin'])->group(function () {
        Route::post('/logout', [LoginController::class, 'logout'])->name('admin.logout');
        Route::get('/attendance/list', [AdminAttendanceController::class, 'index'])->name('admin.attendance.index');
        Route::get('/staff/list', [StaffController::class, 'index'])->name('admin.staff.index');
        Route::get('/attendance/staff/{id}', [AdminAttendanceController::class, 'staffAttendances'])->name('admin.staff_attendance');
        Route::get('/attendance/{user}/detail_or_create/{date}', [AdminAttendanceController::class, 'detailOrCreate'])->name('admin.detail_or_create');
        Route::get('/attendance/{id}', [AdminAttendanceController::class, 'show'])->name('admin.attendance.show');
        Route::put('/attendance/{id}', [AdminAttendanceController::class, 'update'])->name('admin.attendance.update');
        Route::get('/attendance/staff/{id}/csv', [AdminAttendanceController::class, 'export'])->name('admin.csv');
    });
});
Route::middleware(['auth:admin'])->group(function () {
    Route::get('/stamp_correction_request/approve/{attendance_correct_request}', [AdminAttendanceRequestController::class, 'show'])->name('admin.request');
    Route::post('/stamp_correction_request/approve/{attendance_correct_request}', [AdminAttendanceRequestController::class, 'approve'])->name('admin.approve');
});

// ユーザー・管理者同一パスのルート
Route::middleware(['auth:admin,web'])->group(function () {
    Route::get('/stamp_correction_request/list', function (Request $request) {
        /** @var \App\Models\User|null $webUser */
        $webUser = Auth::guard('web')->user();
        if ($webUser && !$webUser->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }
        return app(UserAttendanceRequestController::class)->index($request);
    })->name('attendance_requests.index');
});

// メール認証に関するルート
Route::get('/email/verify', function () {
    return view('user.verify_email');
})->middleware(['auth:web'])->name('verification.notice');
Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill();
    return redirect('/attendance');
})->middleware(['auth:web', 'signed'])->name('verification.verify');
Route::post('/email/verification-notification', function (Request $request) {
    $request->user()->sendEmailVerificationNotification();
    return back()->with('status', 'verification-link-sent');
})->middleware(['auth:web', 'throttle:6,1'])->name('verification.send');

Route::get('/', function () {
    if (Auth::guard('web')->check()) {
        return redirect('/attendance');
    }
    if (Auth::guard('admin')->check()) {
        return redirect('/admin/attendance/list');
    }
    return redirect('/login');
});