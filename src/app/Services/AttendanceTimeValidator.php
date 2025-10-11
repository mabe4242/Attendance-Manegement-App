<?php

namespace App\Services;

use Illuminate\Validation\Validator;
use Carbon\Carbon;

class AttendanceTimeValidator
{
    public static function isValidTime(?string $time)
    {
        if (!$time) return false;
        return preg_match('/^(?:[01]\d|2[0-4]):[0-5]\d$/', $time);
    }

    public static function isBefore(string $time1, string $time2)
    {
        if (!self::isValidTime($time1) || !self::isValidTime($time2)) return true;
        return Carbon::createFromFormat('H:i', $time1)->lt(Carbon::createFromFormat('H:i', $time2));
    }

    public static function isBeforeOrEqual(string $time1, string $time2)
    {
        if (!self::isValidTime($time1) || !self::isValidTime($time2)) return true;
        return Carbon::createFromFormat('H:i', $time1)->lte(Carbon::createFromFormat('H:i', $time2));
    }

    public static function isAfterOrEqual(string $time1, string $time2)
    {
        if (!self::isValidTime($time1) || !self::isValidTime($time2)) return true;
        return Carbon::createFromFormat('H:i', $time1)->gte(Carbon::createFromFormat('H:i', $time2));
    }

    // 休憩の重複チェック
    public static function checkBreakOverlap(Validator $validator, array $breaks)
    {
        $times = [];

        foreach ($breaks as $index => $break) {
            $start = $break['break_start'] ?? null;
            $end   = $break['break_end'] ?? null;

            if (!self::isValidTime($start) || !self::isValidTime($end)) {
                continue;
            }

            $startTime = Carbon::createFromFormat('H:i', $start);
            $endTime   = Carbon::createFromFormat('H:i', $end);

            foreach ($times as $i => [$prevStart, $prevEnd]) {
                if ($startTime->lt($prevEnd) && $endTime->gt($prevStart)) {
                    $validator->errors()->add("breaks.$index.break_start", '休憩時間が他の休憩と重複しています');
                    $validator->errors()->add("breaks.$index.break_end", '休憩時間が他の休憩と重複しています');
                }
            }

            $times[] = [$startTime, $endTime];
        }
    }
}
