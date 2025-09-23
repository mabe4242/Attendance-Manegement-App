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

    //1日分の勤怠データをフォーマット
    public static function formatSingle(Attendance $attendance, Carbon $date)
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

        return (object) [
            'id'           => $attendance->id,
            'date'         => $attendance->date,
            'date_display' => $date->format('m/d'),
            'weekday'      => self::weekdayLabel($date->dayOfWeek),
            'clock_in'     => $attendance->clock_in ? $attendance->clock_in->format('H:i') : '',
            'clock_out'    => $attendance->clock_out ? $attendance->clock_out->format('H:i') : '',
            'break'        => $attendance->clock_in && $attendance->clock_out ? self::formatMinutes($breakMinutes) : '',
            'total_work'   => $attendance->clock_in && $attendance->clock_out ? self::formatMinutes($workMinutes - $breakMinutes) : '',
            'is_future'    => $date->isFuture(),
        ];
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

    public static function formatMonth(Collection $attendances)
    {
        return $attendances->map(fn($attendance) =>
            AttendanceFormatter::formatSingle($attendance, Carbon::parse($attendance->date))
        );
    }
}