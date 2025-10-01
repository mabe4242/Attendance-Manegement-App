<?php

namespace App\Services;

use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AttendanceFormatter
{
    private const MINUTES_PER_HOUR = 60;
    private const ZERO_TIME_FORMAT = '0:00';

    private static function weekdayLabel($dayOfWeek)
    {
        $labels = ['日', '月', '火', '水', '木', '金', '土'];
        return $labels[$dayOfWeek];
    }

    private static function formatMinutes($minutes)
    {
        if ($minutes <= 0) {
            return self::ZERO_TIME_FORMAT;
        }

        $hours = intdiv($minutes, self::MINUTES_PER_HOUR);
        $mins  = $minutes % self::MINUTES_PER_HOUR;

        return sprintf('%d:%02d', $hours, $mins);
    }

    //勤怠の基本集計（勤務時間・休憩時間）
    private static function calculate(Attendance $attendance)
    {
        $workMinutes = 0;
        $breakMinutes = 0;

        if ($attendance->clock_in && $attendance->clock_out) {
            $workMinutes = $attendance->clock_in->diffInMinutes($attendance->clock_out);
        }

        foreach ($attendance->breaks as $break) {
            if ($break->break_start && $break->break_end) {
                $breakMinutes += $break->break_start->diffInMinutes($break->break_end);
            }
        }

        return [
            'workMinutes'  => $workMinutes,
            'breakMinutes' => $breakMinutes,
            'totalMinutes' => $workMinutes - $breakMinutes,
        ];
    }

    // 一般ユーザー用（日単位）
    public static function formatSingle(Attendance $attendance, Carbon $date)
    {
        $calc = self::calculate($attendance);

        return (object) [
            'id'           => $attendance->id,
            'date'         => $attendance->date,
            'date_display' => $date->format('m/d'),
            'weekday'      => self::weekdayLabel($date->dayOfWeek),
            'clock_in'     => $attendance->clock_in ? $attendance->clock_in->format('H:i') : '',
            'clock_out'    => $attendance->clock_out ? $attendance->clock_out->format('H:i') : '',
            'break'        => $attendance->clock_in && $attendance->clock_out ? self::formatMinutes($calc['breakMinutes']) : '',
            'total_work'   => $attendance->clock_in && $attendance->clock_out ? self::formatMinutes($calc['totalMinutes']) : '',
            'is_future'    => $date->isFuture(),
        ];
    }

    //月次用
    public static function formatMonth(Collection $attendances)
    {
        return $attendances->map(fn($attendance) =>
            AttendanceFormatter::formatSingle($attendance, Carbon::parse($attendance->date))
        );
    }

    //管理者画面用（日単位の一覧）
    public static function formatDay(Collection $attendances)
    {
        return $attendances->map(function ($attendance) {
            $calc = self::calculate($attendance);

            return (object) [
                'id'         => $attendance->id,
                'userName'   => $attendance->user->name,
                'clock_in'   => $attendance->clock_in ? $attendance->clock_in->format('H:i') : '',
                'clock_out'  => $attendance->clock_out ? $attendance->clock_out->format('H:i') : '',
                'break'      => $attendance->clock_in && $attendance->clock_out ? self::formatMinutes($calc['breakMinutes']) : '',
                'total_work' => $attendance->clock_in && $attendance->clock_out ? self::formatMinutes($calc['totalMinutes']) : '',
                'date'       => $attendance->date,
            ];
        });
    }
}