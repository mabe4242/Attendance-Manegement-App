<?php

namespace App\Http\Controllers;

use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use App\Services\AttendanceFormatter;
use App\Services\CarbonCalc;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        // リクエストされた月を取得
        $month = $request->query('month', Carbon::now()->format('Y/m'));
        $startOfMonth = Carbon::createFromFormat('Y/m', $month)->startOfMonth();
        $endOfMonth = Carbon::createFromFormat('Y/m', $month)->endOfMonth();
        Attendance::ensureMonthlyRecords($user->id, $startOfMonth, $endOfMonth);

        // 勤怠データを取得・フォーマットを整形
        $attendanceRecords = Attendance::forUserInMonth($user->id, $startOfMonth, $endOfMonth);
        $attendances = AttendanceFormatter::format($attendanceRecords, $startOfMonth, $endOfMonth);

        $months = CarbonCalc::getMonths($month);
        $prevMonthUrl = route('attendance.index', ['month' => $months['prevMonth']]);
        $nextMonthUrl = route('attendance.index', ['month' => $months['nextMonth']]);

        return view('user.attendance_index', compact('attendances', 'month', 'prevMonthUrl', 'nextMonthUrl'));
    }

    public function create(Request $request)
    {
        $userId = Auth::id();
        $today = now()->toDateString();

        $attendance = Attendance::firstOrCreate(
            ['user_id' => $userId, 'date' => $today],
            ['status' => AttendanceStatus::OFF]
        )->load('breaks');

        return view('user.attendance', compact('attendance'));
    }

    public function store()
    {
        $userId = Auth::id();
        $now = now();

        return DB::transaction(function () use ($userId, $now) {
            $attendance = Attendance::getOrCreateToday($userId, $now, AttendanceStatus::OFF);

            if ($attendance->status !== AttendanceStatus::OFF) {
                return back();
            }

            $attendance->update([
                'clock_in' => $now,
                'status' => AttendanceStatus::WORKING,
            ]);

            return redirect()->route('attendance.create');
        });
    }

    public function checkout()
    {
        $userId = Auth::id();
        $now = now();

        return DB::transaction(function () use ($userId, $now) {
            $attendance = Attendance::forTodayWithLock($userId, $now)->firstOrFail();

            if (! in_array($attendance->status, [AttendanceStatus::WORKING, AttendanceStatus::BREAK], true)) {
                return back();
            }

            // 休憩中なら自動で休憩を閉じる
            if ($attendance->status === AttendanceStatus::BREAK) {
                $open = $attendance->openBreak();
                if ($open) {
                    $open->update(['break_end' => $now]);
                }
            }

            $attendance->update([
                'clock_out' => $now,
                'status' => AttendanceStatus::FINISHED,
            ]);

            return redirect()->route('attendance.create');
        });
    }
}
