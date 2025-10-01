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

    public static function updateAttendance(Attendance $attendance, array $data)
    {
        $date = self::buildDate($data['year'], $data['month_day']);

        $attendance->update([
            'date'      => $date,
            'clock_in'  => self::buildDateTime($date, $data['clock_in'] ?? null),
            'clock_out' => self::buildDateTime($date, $data['clock_out'] ?? null),
            'reason'    => $data['reason'] ?? null,
        ]);
    }

    public static function updateBreaks(Attendance $attendance, array $breaks, string $year, string $monthDay)
    {
        $date = self::buildDate($year, $monthDay);

        foreach ($breaks as $breakData) {
            if (empty($breakData['break_start']) && empty($breakData['break_end'])) {
                continue;
            }

            $breakStart = self::buildDateTime($date, $breakData['break_start'] ?? null);
            $breakEnd   = self::buildDateTime($date, $breakData['break_end'] ?? null);

            if (!empty($breakData['id'])) {
                $break = $attendance->breaks->firstWhere('id', $breakData['id']);
                if ($break) {
                    $break->update([
                        'break_start' => $breakStart,
                        'break_end'   => $breakEnd,
                    ]);
                }
            } else {
                $attendance->breaks()->create([
                    'break_start' => $breakStart,
                    'break_end'   => $breakEnd,
                ]);
            }
        }
    }

    private static function buildDate(string $year, string $monthDay)
    {
        return Carbon::createFromFormat('Y年n月j日', $year . $monthDay)->toDateString();
    }

    private static function buildDateTime(string $date, ?string $time)
    {
        return $time ? Carbon::parse("{$date} {$time}") : null;
    }
}
