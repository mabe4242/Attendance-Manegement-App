<?php

namespace App\Models;

use App\Traits\HasBreakFormatting;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BreakRequest extends Model
{
    use HasFactory;
    
    use HasBreakFormatting;

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
}
