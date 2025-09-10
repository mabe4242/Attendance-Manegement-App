<?php

namespace App\Enums;

class AttendanceStatus
{
    public const OFF = 0;

    public const WORKING = 1;

    public const BREAK = 2;

    public const FINISHED = 3;

    public static function label(int $status)
    {
        return match ($status) {
            self::OFF => '勤務外',
            self::WORKING => '出勤中',
            self::BREAK => '休憩中',
            self::FINISHED => '退勤済',
            default => '不明',
        };
    }
}
