<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceStartTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * 出勤ボタンが正しく機能する
     */
    public function attendance_start_test()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create();

        Attendance::factory()->create([
            'user_id' => $user->id,
            'status' => AttendanceStatus::OFF,
        ]);

        // 勤怠打刻画面にアクセスして出勤ボタンが存在することを確認
        $response = $this->actingAs($user)->get(route('attendance.create'));
        $response->assertStatus(200)
                 ->assertSee('出勤');

        $response = $this->post(route('attendance.store'));
        $response->assertRedirect(route('attendance.create'));

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'status' => AttendanceStatus::WORKING,
        ]);

        $response = $this->actingAs($user)->get(route('attendance.create'));
        $response->assertStatus(200)
                 ->assertSeeText('出勤中');
    }

    /**
     * @test
     * 出勤は一日一回のみできる
     */
    public function only_one_start_test()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create();

        Attendance::factory()->finished()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('attendance.create'));
        $response->assertStatus(200)
                 ->assertDontSee('出勤');
    }

    /**
     * @test
     * 出勤時刻が勤怠一覧画面で確認できる
     */
    public function attendance_start_record_test()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create();

        $attendance = Attendance::factory()->notWorking()->create([
            'user_id' => $user->id,
            'date' => Carbon::today(),
        ]);

        $response = $this->actingAs($user)->post(route('attendance.store'));
        $response->assertRedirect(route('attendance.create'));

        $attendance->refresh();
        $this->assertNotNull($attendance->clock_in);
        $this->assertEquals(AttendanceStatus::WORKING, $attendance->status);

        //勤怠一覧画面で出勤時刻を確認
        $listResponse = $this->actingAs($user)->get(route('attendance.index'));
        $listResponse->assertStatus(200)
                    ->assertSee($attendance->clock_in->format('H:i'));
    }
}
