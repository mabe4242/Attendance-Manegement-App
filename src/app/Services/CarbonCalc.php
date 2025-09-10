<?php

namespace App\Services;

use Carbon\Carbon;

class CarbonCalc
{
    public static function getMonths(string $currentMonth)
    {
        $carbonMonth = Carbon::createFromFormat('Y/m', $currentMonth);

        return [
            'prevMonth' => $carbonMonth->copy()->subMonth()->format('Y/m'),
            'nextMonth' => $carbonMonth->copy()->addMonth()->format('Y/m'),
        ];
    }
}
