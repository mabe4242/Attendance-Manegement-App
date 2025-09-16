<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BreakRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_request_id',
        'break_id',
        'break_start',
        'break_end',
    ];

    protected $casts = [
        'break_start' => 'datetime',
        'break_end' => 'datetime',
    ];

    public function attendanceRequest()
    {
        return $this->belongsTo(AttendanceRequest::class);
    }

    public function break()
    {
        return $this->belongsTo(UserBreak::class);
    }

    public function getBreakStartFormattedAttribute()
    {
        return $this->break_start ? Carbon::parse($this->break_start)->format('H:i') : null;
    }

    public function getBreakEndFormattedAttribute()
    {
        return $this->break_end ? Carbon::parse($this->break_end)->format('H:i') : null;
    }
}
