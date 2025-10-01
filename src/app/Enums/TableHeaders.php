<?php

namespace App\Enums;

class TableHeaders
{
    const NAME       = '名前';
    const CLOCK_IN   = '出勤';
    const CLOCK_OUT  = '退勤';
    const BREAK      = '休憩';
    const TOTAL      = '合計';
    const DETAIL     = '詳細';
    const DATE       = '日付';
    const STATUS     = '状態';
    const TARGET_DATETIME = '対象日時';
    const REASON     = '申請理由';
    const REQUESTED_AT = '申請日時';
    const EMAIL      = 'メールアドレス';
    const MONTHLY    = '月次勤怠';

    public static function attendanceDaily()
    {
        return [
            self::NAME,
            self::CLOCK_IN,
            self::CLOCK_OUT,
            self::BREAK,
            self::TOTAL,
            self::DETAIL,
        ];
    }

    public static function attendanceMonthly()
    {
        return [
            self::DATE,
            self::CLOCK_IN,
            self::CLOCK_OUT,
            self::BREAK,
            self::TOTAL,
            self::DETAIL,
        ];
    }

    public static function requests()
    {
        return [
            self::STATUS,
            self::NAME,
            self::TARGET_DATETIME,
            self::REASON,
            self::REQUESTED_AT,
            self::DETAIL,
        ];
    }

    public static function staff()
    {
        return [
            self::NAME,
            self::EMAIL,
            self::MONTHLY,
        ];
    }
}
