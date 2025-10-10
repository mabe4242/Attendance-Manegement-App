<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Attendance;
use App\Models\User;
use App\Models\UserBreak;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDetailTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * 勤怠詳細画面に表示されるデータが選択したものになっている
     */
    public function selected_attendance_detail_correct()
    {
        /** @var \App\Models\Admin $admin */
        $admin = Admin::factory()->create();

        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'name' => 'テストユーザー',
        ]);

        $today = Carbon::today();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => $today->toDateString(),
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00'
        ]);

        $this->actingAs($admin, 'admin');
        $response = $this->get(route('admin.attendance.show', $attendance->id));

        // 勤怠詳細画面で選択した勤怠情報を確認
        $response->assertStatus(200)
            ->assertSee($user->name)
            ->assertSee($today->format('Y年'))
            ->assertSee($today->format('n月j日'))
            ->assertSee('09:00')
            ->assertSee('18:00')
            ->assertSee('8:00'); // 勤務時間
    }

    /**
     * @test
     * 出勤時間が退勤時間より後になっている場合、エラーメッセージが表示される
     */
    public function error_clock_in_is_later_than_clock_out()
    {
        /** @var \App\Models\Admin $admin */
        $admin = Admin::factory()->create();

        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'name' => 'テストユーザー',
        ]);

        $today = Carbon::today();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => $today->toDateString(),
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $this->actingAs($admin, 'admin');

        // 出勤 > 退勤 で更新リクエスト送信
        $response = $this->put(route('admin.attendance.update', $attendance->id), [
            'clock_in' => '20:00',
            'clock_out' => '18:00',
        ]);

        $response->assertSessionHasErrors([
            'clock_in' => '出勤時間もしくは退勤時間が不適切な値です',
        ]);
    }

    /**
     * @test
     * 休憩開始時間が退勤時間より後になっている場合、エラーメッセージが表示される
     */
    public function error_break_start_is_later_than_clock_out()
    {
        /** @var \App\Models\Admin $admin */
        $admin = Admin::factory()->create();

        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'name' => 'テストユーザー',
        ]);

        $today = Carbon::today();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => $today->toDateString(),
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $break = UserBreak::factory()->create([
            'attendance_id' => $attendance->id,
            'break_start' => '12:00:00',
            'break_end' => '13:00:00',
        ]);

        $this->actingAs($admin, 'admin');

        // 休憩開始が退勤より後で更新リクエスト送信
        $response = $this->from(route('admin.attendance.show', $attendance->id))
            ->put(route('admin.attendance.update', $attendance->id), [
                'clock_in' => '09:00',
                'clock_out' => '18:00',
                'year' => $today->year,
                'month_day' => $today->format('m-d'),
                'breaks' => [
                    [
                        'id' => $break->id,
                        'break_start' => '19:00',
                        'break_end' => '20:00',
                    ]
                ],
            ]);

        $response->assertSessionHasErrors();
        $this->assertStringContainsString(
            '休憩時間が不適切な値です',
            collect(session('errors')->all())->join(' ')
        );
    }

    /**
     * @test
     * 休憩終了時間が退勤時間より後になっている場合、エラーメッセージが表示される
     */
    public function error_break_end_is_later_than_clock_out()
    {
        /** @var \App\Models\Admin $admin */
        $admin = Admin::factory()->create();

        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'name' => 'テストユーザー',
        ]);

        $today = Carbon::today();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => $today->toDateString(),
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $break = UserBreak::factory()->create([
            'attendance_id' => $attendance->id,
            'break_start' => '12:00:00',
            'break_end' => '13:00:00',
        ]);

        $this->actingAs($admin, 'admin');

        // 休憩終了が退勤より後で更新リクエスト送信
        $response = $this->from(route('admin.attendance.show', $attendance->id))
            ->put(route('admin.attendance.update', $attendance->id), [
                'clock_in' => '09:00',
                'clock_out' => '18:00',
                'year' => $today->year,
                'month_day' => $today->format('m-d'),
                'breaks' => [
                    [
                        'id' => $break->id,
                        'break_start' => '12:00',
                        'break_end' => '19:00',
                    ],
                ],
            ]);

        // バリデーションエラーを確認
        $response->assertSessionHasErrors();
        $this->assertStringContainsString(
            '休憩時間もしくは退勤時間が不適切な値です',
            collect(session('errors')->all())->join(' ')
        );
    }

    /**
     * @test
     * 備考欄が未入力の場合のエラーメッセージが表示される
     */
    public function error_when_remark_is_empty()
    {
        /** @var \App\Models\Admin $admin */
        $admin = Admin::factory()->create();

        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'name' => 'テストユーザー',
        ]);

        $today = Carbon::today();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => $today->toDateString(),
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'reason' => '修正前は備考を記入',
        ]);

        $break = UserBreak::factory()->create([
            'attendance_id' => $attendance->id,
            'break_start' => '12:00:00',
            'break_end' => '13:00:00',
        ]);

        $this->actingAs($admin, 'admin');

        // 備考欄未入力で更新リクエスト送信
        $response = $this->from(route('admin.attendance.show', $attendance->id))
            ->put(route('admin.attendance.update', $attendance->id), [
                'clock_in' => '09:00',
                'clock_out' => '18:00',
                'year' => $today->year,
                'month_day' => $today->format('m-d'),
                'reason' => '',
                'breaks' => [
                    [
                        'id' => $break->id,
                        'break_start' => '12:00',
                        'break_end' => '13:00',
                    ],
                ],
            ]);

        // バリデーションエラーを確認
        $response->assertSessionHasErrors();
        $this->assertStringContainsString(
            '備考を記入してください',
            collect(session('errors')->all())->join(' ')
        );
    }
}
