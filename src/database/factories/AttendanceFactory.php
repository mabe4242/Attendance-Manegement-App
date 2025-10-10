<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\User;
use App\Enums\AttendanceStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class AttendanceFactory extends Factory
{
    public const START_TIME = 1;
    public const END_TIME = 1;
    public const WORKING_TIME = 8;

    protected $model = Attendance::class;

    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'date' => now()->toDateString(),
            'clock_in' => null,
            'clock_out' => null,
            'status' => AttendanceStatus::OFF,
        ];
    }

    //テストコードで使うステータス指定のメソッド
    public function working()
    {
        return $this->state(function () {
            return [
                'status' => AttendanceStatus::WORKING,
                'clock_in' => now()->subHours(self::START_TIME),
            ];
        });
    }

    public function break()
    {
        return $this->state(function () {
            return [
                'status' => AttendanceStatus::BREAK,
                'clock_in' => now()->subHours(self::START_TIME),
            ];
        });
    }

    public function finished()
    {
        return $this->state(function () {
            return [
                'status' => AttendanceStatus::FINISHED,
                'clock_in' => now()->subHours(self::WORKING_TIME),
                'clock_out' => now()->subHours(self::END_TIME),
            ];
        });
    }

    public function notWorking()
    {
        return $this->state(function () {
            return [
                'status' => AttendanceStatus::OFF,
                'clock_in' => null,
                'clock_out' => null,
            ];
        });
    }
}
