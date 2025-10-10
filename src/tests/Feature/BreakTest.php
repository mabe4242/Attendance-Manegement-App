<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\User;
use App\Models\UserBreak;
use App\Enums\AttendanceStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class BreakTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * 休憩ボタンが正しく機能する
     */
    public function break_start_test()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'status' => AttendanceStatus::WORKING,
            'clock_in' => Carbon::now(),
        ]);

        $response = $this->actingAs($user)->get(route('attendance.create'));
        $response->assertStatus(200)
                 ->assertSee('休憩入');

        $postResponse = $this->actingAs($user)
            ->post(route('break.start', ['id' => $attendance->id]));
        $postResponse->assertRedirect();

        $attendance->refresh();
        $this->assertEquals(AttendanceStatus::BREAK, $attendance->status);

        // 画面上に「休憩中」が表示されていることを確認
        $afterResponse = $this->actingAs($user)->get(route('attendance.create'));
        $afterResponse->assertSee('休憩中');
    }

    /**
     * @test
     * 休憩は一日に何回でもできる
     */
    public function breaks_start_many_times()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'status' => AttendanceStatus::WORKING,
            'clock_in' => now(),
        ]);

        $this->actingAs($user)
            ->post(route('break.start', ['id' => $attendance->id]))
            ->assertRedirect();

        $attendance->refresh();
        $this->assertEquals(AttendanceStatus::BREAK, $attendance->status);

        $this->actingAs($user)
            ->post(route('break.end', ['id' => $attendance->id]))
            ->assertRedirect();

        $attendance->refresh();
        $this->assertEquals(AttendanceStatus::WORKING, $attendance->status);

        // 勤怠打刻画面に「休憩入」ボタンが表示されることを確認
        $response = $this->actingAs($user)->get(route('attendance.create'));
        $response->assertStatus(200)
                ->assertSee('休憩入')
                ->assertSee('出勤中');
    }

    /**
     * @test
     * 休憩戻ボタンが正しく機能する
     */
    public function break_end_test()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'status' => AttendanceStatus::WORKING,
            'clock_in' => now(),
        ]);

        $this->actingAs($user)
            ->post(route('break.start', ['id' => $attendance->id]))
            ->assertRedirect();

        $attendance->refresh();
        $this->assertEquals(AttendanceStatus::BREAK, $attendance->status);
        $this->actingAs($user)
            ->post(route('break.end', ['id' => $attendance->id]))
            ->assertRedirect();

        $attendance->refresh();
        $this->assertEquals(AttendanceStatus::WORKING, $attendance->status);

        // 画面上に「休憩入」ボタンが再表示されていることを確認
        $response = $this->actingAs($user)->get(route('attendance.create'));
        $response->assertStatus(200)
                ->assertSee('休憩入')
                ->assertSee('出勤中');
    }

    /**
     * @test
     * 休憩戻は一日に何回でもできる
     */
    public function breaks_end_many_times()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create();
        $this->actingAs($user);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'status' => AttendanceStatus::WORKING,
            'clock_in' => now(),
        ]);

        $this->actingAs($user)
            ->post(route('break.start', ['id' => $attendance->id]))
            ->assertRedirect();

        $attendance->refresh();
        $this->assertEquals(AttendanceStatus::BREAK, $attendance->status);

        $this->actingAs($user)
            ->post(route('break.end', ['id' => $attendance->id]))
            ->assertRedirect();

        $attendance->refresh();
        $this->assertEquals(AttendanceStatus::WORKING, $attendance->status);

        $this->actingAs($user)
            ->post(route('break.start', ['id' => $attendance->id]))
            ->assertRedirect();

        $attendance->refresh();
        $this->assertEquals(AttendanceStatus::BREAK, $attendance->status);

        $response = $this->actingAs($user)->get(route('attendance.create'));

        // 「休憩戻」ボタンが表示されることを確認
        $response->assertStatus(200)
                ->assertSee('休憩戻');
    }

    /**
     * @test
     * 休憩時刻が勤怠一覧画面で確認できる
     */
    public function break_record_on_attendance_index()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'status' => AttendanceStatus::WORKING,
            'clock_in' => Carbon::now()->subHours(3),
            'date' => Carbon::today(),
        ]);

        $this->actingAs($user);

        // 休憩開始
        $response = $this->post(route('break.start', ['id' => $attendance->id]));

        $this->assertDatabaseHas('breaks', [
            'attendance_id' => $attendance->id,
            'break_end' => null,
        ]);

        // 休憩終了（10分後に戻る）
        $break = UserBreak::where('attendance_id', $attendance->id)->first();
        $break->update(['break_start' => Carbon::now()->subMinutes(10)]);

        $response = $this->post(route('break.end', ['id' => $attendance->id]));

        $this->assertDatabaseMissing('breaks', [
            'attendance_id' => $attendance->id,
            'break_end' => null,
        ]);

        $break->refresh();
        $this->assertNotNull($break->break_end);
        $this->post(route('attendance.checkout'));

        $response = $this->get(route('attendance.index'));

        // 勤怠一覧画面に休憩時間が表示されていることを確認（10分）
        $expectedBreakTime = '0:10';
        $response->assertSee($expectedBreakTime);
    }
}
