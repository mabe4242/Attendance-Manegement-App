<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use App\Enums\AttendanceStatus;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceCheckoutTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * 退勤ボタンが正しく機能する
     */
    public function checkout_successfully()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create();
        $this->actingAs($user);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'status' => AttendanceStatus::WORKING,
            'clock_in' => Carbon::now()->subHours(4),
        ]);

        $response = $this->get(route('attendance.create'));
        $response->assertStatus(200);
        $response->assertSee('退勤');

        $checkoutResponse = $this->post(route('attendance.checkout'));
        $checkoutResponse->assertRedirect(route('attendance.create'));

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'status' => AttendanceStatus::FINISHED,
        ]);

        // 勤怠打刻画面で「退勤済」が表示されていることを確認
        $this->get(route('attendance.create'))
            ->assertStatus(200)
            ->assertSee(AttendanceStatus::label(AttendanceStatus::FINISHED));
    }

    /**
     * @test
     * 退勤時刻が勤怠一覧画面で確認できる
     */
    public function check_out_time_on_attendance_index()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create();
        $this->actingAs($user);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'status' => AttendanceStatus::OFF,
        ]);

        $this->post(route('attendance.store'));
        $this->post(route('attendance.checkout'));

        $response = $this->get(route('attendance.index'));
        $response->assertStatus(200);

        // 退勤時刻が画面に表示されているか確認
        $attendance->refresh(); 
        $response->assertSee($attendance->clock_out->format('H:i'));
    }
}
