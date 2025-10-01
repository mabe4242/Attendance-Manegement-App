<?php

namespace App\Models;

use App\Enums\RequestStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AttendanceRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'attendance_id',
        'admin_id',
        'request_date',
        'clock_in',
        'clock_out',
        'status',
        'reason',
    ];

    protected $casts = [
        'request_date' => 'date',
        'clock_in' => 'datetime',
        'clock_out' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }

    public function breakRequests()
    {
        return $this->hasMany(BreakRequest::class);
    }

    public static function createWithBreaks(array $data, Attendance $attendance)
    {
        $clockIn = $data['clock_in'] ? (clone $attendance->date)->setTimeFromTimeString($data['clock_in']) : null;
        $clockOut = $data['clock_out'] ? (clone $attendance->date)->setTimeFromTimeString($data['clock_out']) : null;

        $attendanceRequest = self::create([
            'user_id' => Auth::id(),
            'attendance_id' => $attendance->id,
            'request_date' => $attendance->date,
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
            'status' => RequestStatus::PENDING,
            'reason' => $data['reason'] ?? null,
        ]);

        foreach ($data['breaks'] ?? [] as $index => $breakData) {
            $attendanceRequest->addBreakRequest($attendance, $index, $breakData);
        }

        return $attendanceRequest;
    }

    public function addBreakRequest(Attendance $attendance, int $index, array $breakData)
    {
        $breakStart = $breakData['break_start'] ?? null;
        $breakEnd = $breakData['break_end'] ?? null;

        if ($breakStart || $breakEnd) {
            $this->breakRequests()->create([
                'break_id' => $attendance->breaks[$index]->id ?? null,
                'break_start' => $breakStart ? (clone $attendance->date)->setTimeFromTimeString($breakStart) : null,
                'break_end' => $breakEnd ? (clone $attendance->date)->setTimeFromTimeString($breakEnd) : null,
            ]);
        }
    }

    public function scopeStatus($query, $status)
    {
        if ($status !== null) {
            return $query->where('status', $status);
        }
        return $query;
    }

    public function scopeLatestOrder($query)
    {
        return $query->orderBy('request_date', 'desc')
                     ->orderBy('created_at', 'desc');
    }
}
