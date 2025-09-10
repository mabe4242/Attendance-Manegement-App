<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;

class AttendanceFormatter
{
    /**
     * 指定された日付範囲でユーザーの勤怠データを整形して返す
     *
     * @param  \Illuminate\Support\Collection  $attendanceRecords  (dateをキーにしたコレクション)
     */
    public static function format(Collection $attendanceRecords, Carbon $startOfMonth, Carbon $endOfMonth): Collection
    {
        $attendances = collect();
        $currentDate = $startOfMonth->copy();

        while ($currentDate->lte($endOfMonth)) {
            $record = $attendanceRecords->get($currentDate->toDateString());

            $attendances->push(
                self::formatOneDay($record, $currentDate)
            );

            $currentDate->addDay();
        }

        return $attendances;
    }

    // 1日分の勤怠データをフォーマットする
    protected static function formatOneDay($record, Carbon $date): object
    {
        if ($record) {
            $clockIn = $record->clock_in ? Carbon::parse($record->clock_in) : null;
            $clockOut = $record->clock_out ? Carbon::parse($record->clock_out) : null;

            // 休憩合計時間（分）
            $totalBreakMinutes = $record->breaks->reduce(function ($carry, $break) {
                if ($break->break_start && $break->break_end) {
                    $carry += Carbon::parse($break->break_start)->diffInMinutes(Carbon::parse($break->break_end));
                }

                return $carry;
            }, 0);

            // 勤務時間（分）
            $workMinutes = 0;
            if ($clockIn && $clockOut) {
                $workMinutes = $clockIn->diffInMinutes($clockOut) - $totalBreakMinutes;
            }

            return (object) [
                'id' => $record->id,
                'date' => $date->toDateString(),
                'date_display' => $date->format('m/d'),
                'weekday' => $date->isoFormat('ddd'),
                'clock_in' => $clockIn ? $clockIn->format('H:i') : '',
                'clock_out' => $clockOut ? $clockOut->format('H:i') : '',
                'break' => $totalBreakMinutes > 0
                    ? sprintf('%d:%02d', floor($totalBreakMinutes / 60), $totalBreakMinutes % 60)
                    : '',
                'total_work' => $workMinutes > 0
                    ? sprintf('%d:%02d', floor($workMinutes / 60), $workMinutes % 60)
                    : '',
            ];
        }

        // 出勤記録なし
        return (object) [
            'id' => null,
            'date' => $date->toDateString(),
            'date_display' => $date->format('m/d'),
            'weekday' => $date->isoFormat('ddd'),
            'clock_in' => '',
            'clock_out' => '',
            'break' => '',
            'total_work' => '',
        ];
    }
}
