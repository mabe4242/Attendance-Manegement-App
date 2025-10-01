<?php

namespace App\Models;

use App\Traits\HasBreakFormatting;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class UserBreak extends Model
{
    use HasBreakFormatting;

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

    public function breakRequests()
    {
        return $this->hasMany(BreakRequest::class, 'break_id');
    }

    protected function breakStartFormatted()
    {
        return Attribute::get(function ($value, $attributes) {
            return $attributes['break_start']
                ? Carbon::parse($attributes['break_start'])->format('H:i')
                : null;
        });
    }

    protected function breakEndFormatted()
    {
        return Attribute::get(function ($value, $attributes) {
            return $attributes['break_end']
                ? Carbon::parse($attributes['break_end'])->format('H:i')
                : null;
        });
    }
}
