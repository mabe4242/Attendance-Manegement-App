<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserBreak extends Model
{
    protected $table = 'breaks';

    protected $fillable = [
        'attendance_id',
        'break_start',
        'break_end',
    ];

    protected $casts = [
        'break_start' => 'datetime',
        'break_end' => 'datetime',
    ];

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }
}
