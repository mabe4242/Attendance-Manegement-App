<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use App\Models\User;
use App\Models\UserBreak;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceIndexTest extends TestCase
{
    use RefreshDatabase;

    /** 
     * @test 
     * 自分が行った勤怠情報が全て表示されている
     */
    public function all_attendance_records_on_index()
    {
        // 現在日付を基準に動的に作成
        $clockInTime = Carbon::now()->setTime(9, 0, 0);
        $clockOutTime = Carbon::now()->setTime(18, 0, 0);

        /** @var \App\Models\User $user */
        $user = User::factory()->create();

        $attendances = Attendance::factory()
            ->count(3)
            ->for($user)
            ->sequence(
                ['date' => Carbon::now()->startOfMonth()],
                ['date' => Carbon::now()->startOfMonth()->addDay()],
                ['date' => Carbon::now()->startOfMonth()->addDays(2)],
            )
            ->create([
                'clock_in' => $clockInTime,
                'clock_out' => $clockOutTime,
                'status' => AttendanceStatus::WORKING,
            ]);

        // 各勤怠に対応する休憩データを登録
        foreach ($attendances as $attendance) {
            UserBreak::factory()->create([
                'attendance_id' => $attendance->id,
                'break_start' => Carbon::parse($attendance->clock_in)->addHours(3),
                'break_end' => Carbon::parse($attendance->clock_in)->addHours(4),
            ]);
        }

        $this->actingAs($user);
        $response = $this->get(route('attendance.index'));
        $response->assertStatus(200);

        // 自分の勤怠情報がすべて表示されていることを確認
        foreach ($attendances as $attendance) {
            $dateDisplay = $attendance->date->format('Y-m-d');
            $response->assertSee($dateDisplay);
            $response->assertSee('09:00'); // 出勤時刻
            $response->assertSee('18:00'); // 退勤時刻
            $response->assertSee('1:00'); // 休憩合計（1時間）
            $response->assertSee('8:00'); // 勤務時間（9時間 - 1時間休憩）
        }
    }

    /**
     * @test
     * 勤怠一覧画面に遷移した際に現在の月が表示される
     */
    public function current_month_on_index()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create();
        $this->actingAs($user);
        $response = $this->get(route('attendance.index'));
        $response->assertStatus(200);

        // 現在の月が画面に表示されていることを確認
        $currentMonth = now()->format('Y/m');
        $response->assertSee($currentMonth);
    }

    /**
     * @test
     * 「前月」を押下した時に表示月の前月の情報が表示される
     */
    public function previous_month_on_index()
    {
        // 現在日付と前月日付を動的に生成
        $currentMonth = Carbon::now()->startOfMonth();
        $previousMonth = Carbon::now()->subMonth()->startOfMonth();

        /** @var \App\Models\User $user */
        $user = User::factory()->create();

        // 前月の勤怠データ作成
        $attendancePrev = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => $previousMonth->copy()->addDays(2)->toDateString(),
            'clock_in' => $previousMonth->copy()->addDays(2)->setTime(9, 0, 0),
            'clock_out' => $previousMonth->copy()->addDays(2)->setTime(18, 0, 0),
        ]);

        // 前月の休憩データ
        UserBreak::factory()->create([
            'attendance_id' => $attendancePrev->id,
            'break_start' => $previousMonth->copy()->addDays(2)->setTime(12, 0, 0),
            'break_end' => $previousMonth->copy()->addDays(2)->setTime(13, 0, 0),
        ]);

        // 今月の勤怠データ
        Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => $currentMonth->copy()->addDays(3)->toDateString(),
            'clock_in' => $currentMonth->copy()->addDays(3)->setTime(9, 0, 0),
            'clock_out' => $currentMonth->copy()->addDays(3)->setTime(18, 0, 0),
        ]);

        $response = $this->actingAs($user)->get(route('attendance.index'));
        $response->assertStatus(200);
        $response->assertSee($currentMonth->format('Y/m'));

        $prevMonthUrl = route('attendance.index', ['month' => $previousMonth->format('Y/m')]);
        $response->assertSee($prevMonthUrl);

        // 「前月」ボタン押下
        $responsePrev = $this->actingAs($user)->get(route('attendance.index', [
            'month' => $previousMonth->format('Y/m'),
        ]));

        // 前月が表示されているか確認
        $responsePrev->assertStatus(200)
            ->assertSee($previousMonth->format('Y/m'))
            ->assertSee($previousMonth->copy()->addDays(2)->format('m/d')) // 前月データの日付
            ->assertSee('9:00')
            ->assertSee('18:00')
            ->assertSee('1:00') // 休憩時間
            ->assertSee('8:00'); // 勤務時間
    }

    /**
     * @test
     * 「翌月」を押下した時に表示月の前月の情報が表示される
     */
    public function next_month_on_index()
    {
        // 現在日付と翌月日付を動的に生成
        $currentMonth = Carbon::now()->startOfMonth();
        $nextMonth = Carbon::now()->addMonth()->startOfMonth();

        /** @var \App\Models\User $user */
        $user = User::factory()->create();

        // 翌月の勤怠データ作成
        $attendanceNext = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => $nextMonth->copy()->addDays(2)->toDateString(),
            'clock_in' => $nextMonth->copy()->addDays(2)->setTime(9, 0, 0),
            'clock_out' => $nextMonth->copy()->addDays(2)->setTime(18, 0, 0),
        ]);

        // 翌月の休憩データ
        UserBreak::factory()->create([
            'attendance_id' => $attendanceNext->id,
            'break_start' => $nextMonth->copy()->addDays(2)->setTime(12, 0, 0),
            'break_end' => $nextMonth->copy()->addDays(2)->setTime(13, 0, 0),
        ]);

        // 今月の勤怠データ（表示対象外）
        Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => $currentMonth->copy()->addDays(3)->toDateString(),
            'clock_in' => $currentMonth->copy()->addDays(3)->setTime(9, 0, 0),
            'clock_out' => $currentMonth->copy()->addDays(3)->setTime(18, 0, 0),
        ]);

        $response = $this->actingAs($user)->get(route('attendance.index'));
        $response->assertStatus(200);
        $response->assertSee($currentMonth->format('Y/m'));

        $nextMonthUrl = route('attendance.index', ['month' => $nextMonth->format('Y/m')]);
        $response->assertSee($nextMonthUrl);

        $responseNext = $this->actingAs($user)->get(route('attendance.index', [
            'month' => $nextMonth->format('Y/m'),
        ]));

        // 翌月が表示されているか確認
        $responseNext->assertStatus(200)
            ->assertSee($nextMonth->format('Y/m'))
            ->assertSee($nextMonth->copy()->addDays(2)->format('m/d')) // 翌月データの日付
            ->assertSee('9:00')
            ->assertSee('18:00')
            ->assertSee('1:00') // 休憩時間
            ->assertSee('8:00'); // 勤務時間
    }

    /**
     * @test
     * 「詳細」を押下すると、その日の勤怠詳細画面に遷移する
     */
    public function attendance_detail_page_test()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => Carbon::now()->toDateString(),
            'clock_in' => Carbon::now()->setTime(9, 0, 0),
            'clock_out' => Carbon::now()->setTime(18, 0, 0),
        ]);

        // 休憩データ作成
        UserBreak::factory()->create([
            'attendance_id' => $attendance->id,
            'break_start' => Carbon::now()->setTime(12, 0, 0),
            'break_end' => Carbon::now()->setTime(13, 0, 0),
        ]);

        $response = $this->actingAs($user)->get(route('attendance.index'));
        $response->assertStatus(200);

        $detailUrl = route('attendance.detail', ['id' => $attendance->id]);
        $response->assertSee('<button', false); 
        $response->assertSee('詳細');

        // 勤怠詳細ページが表示されることを確認
        $responseDetail = $this->actingAs($user)->get($detailUrl);
        $responseDetail->assertStatus(200)
            ->assertSee('勤怠詳細')
            ->assertSee(Carbon::now()->format('n月j日'))
            ->assertSee($user->name)
            ->assertSee('9:00')
            ->assertSee('18:00');
    }
}
