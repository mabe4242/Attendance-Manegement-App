<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\BreakRequest;
use App\Models\AttendanceRequest;
use Carbon\Carbon;

class BreakRequestFactory extends Factory
{
    protected $model = BreakRequest::class;

    public function definition()
    {
        $clockIn = Carbon::now()->subHours(rand(1, 9));
        $breakStart = $clockIn->copy()->addHours(3)->addMinutes(rand(0,30));
        $breakEnd = $breakStart->copy()->addHour();

        return [
            'attendance_request_id' => AttendanceRequest::factory(),
            'break_id' => null,
            'break_start' => $breakStart,
            'break_end' => $breakEnd,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
