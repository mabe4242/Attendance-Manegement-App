<?php

namespace App\Traits;

use Carbon\Carbon;

trait HasBreakFormatting
{
    public function getBreakStartFormattedAttribute()
    {
        return $this->break_start ? Carbon::parse($this->break_start)->format('H:i') : null;
    }

    public function getBreakEndFormattedAttribute()
    {
        return $this->break_end ? Carbon::parse($this->break_end)->format('H:i') : null;
    }
}
