<?php

namespace App\Services;

use App\Models\Attendance;
use Carbon\Carbon;

class AttendanceService
{
    // 当日の勤怠を取得 or 作成
    public static function getOrCreateToday(int $userId, Carbon $now, $defaultStatus): Attendance
    {
        $attendance = Attendance::forTodayWithLock($userId, $now)->first();

        if (!$attendance) {
            $attendance = Attendance::create([
                'user_id' => $userId,
                'date' => $now->toDateString(),
                'status' => $defaultStatus,
            ]);
        }

        return $attendance;
    }

    // 月次勤怠を取得（存在しない日付は空レコード）
    public static function getMonthlyAttendances(int $userId, Carbon $startOfMonth, Carbon $endOfMonth)
    {
        $attendanceRecords = Attendance::forUserInMonth($userId, $startOfMonth, $endOfMonth)
            ->keyBy(fn($item) => $item->date->toDateString());

        $attendances = collect();
        $currentDate = $startOfMonth->copy();

        while ($currentDate->lte($endOfMonth)) {
            $dateStr = $currentDate->toDateString();
            $attendances->push($attendanceRecords->get($dateStr) ?? new Attendance([
                'id' => null,
                'user_id' => $userId,
                'date' => $dateStr,
                'clock_in' => null,
                'clock_out' => null,
            ]));
            $currentDate->addDay();
        }

        return $attendances;
    }

    // 日付単位で取得
    public static function getDailyAttendances(Carbon $date)
    {
        return Attendance::with(['user', 'breaks'])->whereDate('date', $date)->get();
    }
}
