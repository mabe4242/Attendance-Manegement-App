<?php

namespace App\Models;

use App\Traits\AttendanceFormat;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attendance extends Model
{
    use AttendanceFormat;

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

    // 特定の申請 or 最新の申請を返す
    public function getRequest(?int $requestId = null)
    {
        return $this->attendanceRequests()
            ->with(['breakRequests', 'admin'])
            ->when($requestId, fn($q) => $q->where('id', $requestId), fn($q) => $q->latest())
            ->first();
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
}
