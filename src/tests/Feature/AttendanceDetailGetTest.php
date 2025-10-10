<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\User;
use App\Models\UserBreak;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceDetailGetTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * 勤怠詳細画面の「名前」がログインユーザーの氏名になっている
     */
    public function user_name_on_attendance_detail()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create();

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => Carbon::now()->toDateString(),
            'clock_in' => Carbon::now()->setTime(9, 0, 0),
            'clock_out' => Carbon::now()->setTime(18, 0, 0),
        ]);

        $response = $this->actingAs($user)->get(route('attendance.detail', ['id' => $attendance->id]));
        $response->assertStatus(200);

        // 名前が表示されているか確認
        $response->assertSee($user->name);
    }

    /**
     * @test
     * 勤怠詳細画面の「日付」が選択した日付になっている
     */
    public function selected_date_on_attendance_detail()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create();

        // 任意の日付を設定
        $selectedDate = Carbon::now()->subDays(3);
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => $selectedDate->toDateString(),
            'clock_in' => $selectedDate->copy()->setTime(9, 0, 0),
            'clock_out' => $selectedDate->copy()->setTime(18, 0, 0),
        ]);

        $response = $this->actingAs($user)->get(route('attendance.detail', ['id' => $attendance->id]));
        $response->assertStatus(200);
        $response->assertSee($selectedDate->format('n月j日'));
    }

    /**
     * @test
     *「出勤・退勤」にて記されている時間がログインユーザーの打刻と一致している
     */
    public function correct_clock_in_and_out_times()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create();

        // 出勤・退勤時刻を設定（9:00〜18:00）
        $clockIn = Carbon::now()->setTime(9, 0, 0);
        $clockOut = Carbon::now()->setTime(18, 0, 0);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => Carbon::now()->toDateString(),
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
        ]);

        UserBreak::factory()->create([
            'attendance_id' => $attendance->id,
            'break_start' => Carbon::now()->setTime(12, 0, 0),
            'break_end' => Carbon::now()->setTime(13, 0, 0),
        ]);

        $response = $this->actingAs($user)->get(route('attendance.detail', ['id' => $attendance->id]));
        $response->assertStatus(200);

        // 勤怠詳細画面上でユーザーの打刻と一致しているか確認
        $response
            ->assertSee($clockIn->format('H:i'))
            ->assertSee($clockOut->format('H:i'));
    }

    /**
     * @test
     * 「休憩」にて記されている時間がログインユーザーの打刻と一致している
     */
    public function correct_break_times()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'clock_in' => '2025-10-01 09:00:00',
            'clock_out' => '2025-10-01 18:00:00',
        ]);

        $break1 = UserBreak::factory()->create([
            'attendance_id' => $attendance->id,
            'break_start' => '2025-10-01 12:00:00',
            'break_end' => '2025-10-01 12:30:00',
        ]);

        $break2 = UserBreak::factory()->create([
            'attendance_id' => $attendance->id,
            'break_start' => '2025-10-01 15:00:00',
            'break_end' => '2025-10-01 15:15:00',
        ]);

        $this->actingAs($user);
        $response = $this->get(route('attendance.detail', $attendance->id));
        $response->assertStatus(200);

        // 休憩時間が勤怠詳細画面に表示されているか確認
        $response->assertSee('12:00')
                 ->assertSee('12:30')
                 ->assertSee('15:00')
                 ->assertSee('15:15');
    }
}
