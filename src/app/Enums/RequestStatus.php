<?php

namespace App\Enums;

class RequestStatus
{
    public const PENDING = 0;

    public const APPROVED = 1;

    public static function label(int $status)
    {
        return match ($status) {
            self::PENDING => '承認待ち',
            self::APPROVED => '承認済み',
            default => '不明',
        };
    }
}
