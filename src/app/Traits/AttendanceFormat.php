<?php

namespace App\Traits;

use Carbon\Carbon;
use Carbon\CarbonInterval;

trait AttendanceFormat
{
    // 休憩合計（分）
    public function getBreaksTotalMinutesAttribute()
    {
        if (!$this->relationLoaded('breaks')) {
            $this->load('breaks');
        }

        return $this->breaks->reduce(function ($carry, $break) {
            if ($break->break_start && $break->break_end) {
                $carry += $break->break_end->diffInMinutes($break->break_start);
            }
            return $carry;
        }, 0);
    }

    // 勤務時間合計（分）
    public function getTotalWorkMinutesAttribute()
    {
        if (!$this->clock_in || !$this->clock_out) {
            return null;
        }

        return max(0, $this->clock_out->diffInMinutes($this->clock_in) - $this->breaks_total_minutes);
    }

    // フォーマット済み休憩合計
    public function getBreaksTotalFormattedAttribute()
    {
        if ($this->breaks_total_minutes === 0) return '';
        return CarbonInterval::minutes($this->breaks_total_minutes)->cascade()->format('%h:%I');
    }

    // フォーマット済み勤務時間合計
    public function getTotalWorkFormattedAttribute()
    {
        if ($this->total_work_minutes === null) return '';
        return CarbonInterval::minutes($this->total_work_minutes)->cascade()->format('%h:%I');
    }

    // 日付フォーマット
    public function getYearFormattedAttribute()
    {
        return $this->date ? $this->date->format('Y年') : null;
    }

    public function getMonthDayFormattedAttribute()
    {
        return $this->date ? $this->date->format('m月d日') : null;
    }

    public function getClockInFormattedAttribute()
    {
        return $this->clock_in ? $this->clock_in->format('H:i') : null;
    }

    public function getClockOutFormattedAttribute()
    {
        return $this->clock_out ? $this->clock_out->format('H:i') : null;
    }

    public function getYearAttribute()
    {
        return $this->date ? Carbon::parse($this->date)->format('Y年') : null;
    }

    public function getMonthDayAttribute()
    {
        return $this->date ? Carbon::parse($this->date)->format('n月j日') : null;
    }
}
