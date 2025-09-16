<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
}
