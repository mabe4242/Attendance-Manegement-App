<?php

namespace App\Models;

use Carbon\Carbon;
use Carbon\CarbonInterval;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attendance extends Model
{
    protected $fillable = [
        'user_id',
        'date',
        'clock_in',
        'clock_out',
        'status',
    ];

    protected $casts = [
        'date' => 'date',
        'clock_in' => 'datetime',
        'clock_out' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function breaks(): HasMany
    {
        return $this->hasMany(UserBreak::class);
    }

    public function attendanceRequests(): HasMany
    {
        return $this->hasMany(AttendanceRequest::class);
    }

    // 進行中（未終了）の休憩を1件返すヘルパ
    public function openBreak(): ?UserBreak
    {
        return $this->breaks()->whereNull('break_end')->latest('break_start')->first();
    }

    // 勤怠データをユーザーID・月単位で取得するスコープ
    public function scopeForUserInMonth($query, int $userId, Carbon $startOfMonth, Carbon $endOfMonth)
    {
        return $query->with('breaks')
            ->where('user_id', $userId)
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->get();
    }

    // 当日の勤怠レコードを取得してロック
    public function scopeForTodayWithLock($query, $userId, Carbon $now)
    {
        return $query->where('user_id', $userId)
            ->where('date', $now->toDateString())
            ->lockForUpdate();
    }

    // 当日の勤怠レコードを取得 or 作成
    public static function getOrCreateToday($userId, Carbon $now, $defaultStatus)
    {
        $attendance = self::forTodayWithLock($userId, $now)->first();

        if (! $attendance) {
            $attendance = self::create([
                'user_id' => $userId,
                'date' => $now->toDateString(),
                'status' => $defaultStatus,
            ]);
        }

        return $attendance;
    }

    public static function getMonthlyAttendances(int $userId, Carbon $startOfMonth, Carbon $endOfMonth)
    {
        $attendanceRecords = self::forUserInMonth($userId, $startOfMonth, $endOfMonth)
            ->keyBy(fn($item) => Carbon::parse($item->date)->toDateString());

        $attendances = collect();
        $currentDate = $startOfMonth->copy();
        while ($currentDate->lte($endOfMonth)) {
            $dateStr = $currentDate->toDateString();

            $record = $attendanceRecords->get($dateStr) ?? new self([
                'id' => null,
                'user_id' => $userId,
                'date' => $dateStr,
                'clock_in' => null,
                'clock_out' => null,
            ]);

            $attendances->push($record);
            $currentDate->addDay();
        }

        return $attendances;
    }

    //休憩時間の合計（分単位）
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

    //勤務時間合計（分単位）
    public function getTotalWorkMinutesAttribute()
    {
        if (!$this->clock_in || !$this->clock_out) {
            return null;
        }

        $totalMinutes = $this->clock_out->diffInMinutes($this->clock_in);
        return max(0, $totalMinutes - $this->breaks_total_minutes);
    }

    //フォーマット済みの休憩合計 (例: 1:30)
    public function getBreaksTotalFormattedAttribute()
    {
        if ($this->breaks_total_minutes === 0) {
            return '';
        }
        return CarbonInterval::minutes($this->breaks_total_minutes)->cascade()->format('%h:%I');
    }
    
    //フォーマット済みの勤務合計 (例: 8:15)
    public function getTotalWorkFormattedAttribute()
    {
        if ($this->total_work_minutes === null) {
            return '';
        }
        return CarbonInterval::minutes($this->total_work_minutes)->cascade()->format('%h:%I');
    }

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
