<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use App\Models\User;
use App\Models\Attendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatusConfirmationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * 勤務外の場合、勤怠ステータスが正しく表示される
     */
    public function status_is_displayed_as_off()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create();

        Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'status' => AttendanceStatus::OFF,
        ]);

        $response = $this->actingAs($user)->get(route('attendance.create'));
        $response->assertStatus(200)
                 ->assertSee('勤務外');
    }

    /**
     * @test
     * 出勤中の場合、勤怠ステータスが正しく表示される
     */
    public function status_is_displayed_as_working()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create();

        Attendance::factory()->working()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('attendance.create'));
        $response->assertStatus(200)
                 ->assertSeeText('出勤中');
    }

    /**
     * @test
     * 休憩中の場合、勤怠ステータスが正しく表示される
     */
    public function status_is_displayed_as_break()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create();

        Attendance::factory()->break()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('attendance.create'));
        $response->assertStatus(200)
                 ->assertSeeText('休憩中');
    }

    /**
     * @test
     * 退勤済の場合、勤怠ステータスが正しく表示される
     */
    public function status_is_displayed_as_finished()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create();

        Attendance::factory()->finished()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('attendance.create'));
        $response->assertStatus(200)
                 ->assertSeeText('退勤済');
    }
}
