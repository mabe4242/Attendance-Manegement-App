<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\UserBreak;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserBreakFactory extends Factory
{
    protected $model = UserBreak::class;

    public function definition()
    {
        $start = $this->faker->dateTimeBetween('-1 days', 'now');
        $end = (clone $start)->modify('+10 minutes');

        return [
            'attendance_id' => Attendance::factory(),
            'break_start' => $start,
            'break_end' => $end,
        ];
    }
}
