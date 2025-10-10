<?php

namespace Database\Factories;

use App\Models\AttendanceRequest;
use App\Models\User;
use App\Models\Attendance;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Enums\RequestStatus;

class AttendanceRequestFactory extends Factory
{
    protected $model = AttendanceRequest::class;

    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'attendance_id' => Attendance::factory(),
            'admin_id' => null,
            'request_date' => $this->faker->date(),
            'clock_in' => $this->faker->time('H:i'),
            'clock_out' => $this->faker->time('H:i'),
            'status' => RequestStatus::PENDING,
            'reason' => $this->faker->sentence(),
        ];
    }
}
