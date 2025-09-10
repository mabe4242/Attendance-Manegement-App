<?php

namespace App\Models;

use Carbon\Carbon;
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
            ->get()
            ->mapWithKeys(function ($item) {
                return [Carbon::parse($item->date)->toDateString() => $item];
            });
    }

    // ユーザーの指定月の勤怠レコードが存在しなければ生成
    public static function ensureMonthlyRecords(int $userId, Carbon $startOfMonth, Carbon $endOfMonth)
    {
        $currentDate = $startOfMonth->copy();

        while ($currentDate->lte($endOfMonth)) {
            self::firstOrCreate(
                [
                    'user_id' => $userId,
                    'date' => $currentDate->toDateString(),
                ],
                [
                    'clock_in' => null,
                    'clock_out' => null,
                ]
            );
            $currentDate->addDay();
        }
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
}
