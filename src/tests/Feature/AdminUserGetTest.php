<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserGetTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * 管理者ユーザーが全一般ユーザーの「氏名」「メールアドレス」を確認できる
     */
    public function all_users_name_and_email()
    {
        /** @var \App\Models\Admin $admin */
        $admin = Admin::factory()->create();
        $this->actingAs($admin, 'admin');

        /** @var \App\Models\User $user */
        $users = collect([
            User::factory()->create(['name' => 'テスト1', 'email' => '1@example.com']),
            User::factory()->create(['name' => 'テスト2', 'email' => '22@example.com']),
            User::factory()->create(['name' => 'テスト3', 'email' => '333@example.com']),
        ]);

        $response = $this->get(route('admin.staff.index'));
        $response->assertStatus(200);

        // 各ユーザーの氏名とメールアドレスが表示されていることを確認
        foreach ($users as $user) {
            $response->assertSee($user->name);
            $response->assertSee($user->email);
        }
    }

    /**
     * @test
     * ユーザーの勤怠情報が正しく表示される
     */
    public function view_user_monthly_attendance()
    {
        /** @var \App\Models\Admin $admin */
        $admin = Admin::factory()->create();
        $this->actingAs($admin, 'admin');

        /** @var \App\Models\User $user */
        $user = User::factory()->create(['name' => 'テストユーザー']);

        $today = Carbon::today();
        $attendances = collect([
            Attendance::factory()->create([
                'user_id' => $user->id,
                'date' => $today->startOfMonth()->toDateString(),
                'clock_in' => '09:00:00',
                'clock_out' => '18:00:00',
            ]),
            Attendance::factory()->create([
                'user_id' => $user->id,
                'date' => $today->startOfMonth()->addDay()->toDateString(),
                'clock_in' => '10:00:00',
                'clock_out' => '19:00:00',
            ]),
        ]);

        // 選択ユーザーの月次勤怠一覧画面にアクセス
        $response = $this->get(route('admin.staff_attendance', ['id' => $user->id]));
        $response->assertStatus(200);
        $response->assertSee($user->name);

        // 各勤怠情報が画面に表示されていることを確認
        foreach ($attendances as $attendance) {
            $response->assertSee($attendance->date->format('m/d'));
            $response->assertSee(substr($attendance->clock_in, 0, 5));
            $response->assertSee(substr($attendance->clock_out, 0, 5));
        }
    }

    /**
     * @test
     * 「前月」を押下した時に表示月の前月の情報が表示される
     */
    public function view_previous_month_attendance()
    {
        /** @var \App\Models\Admin $admin */
        $admin = Admin::factory()->create();
        $this->actingAs($admin, 'admin');

        /** @var \App\Models\User $user */
        $user = User::factory()->create(['name' => 'テストユーザー']);

        $today = Carbon::today();
        $firstDayOfThisMonth = $today->copy()->startOfMonth();
        $firstDayOfLastMonth = $today->copy()->subMonth()->startOfMonth();

        $attendanceThisMonth = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => $firstDayOfThisMonth->toDateString(),
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $attendanceLastMonth = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => $firstDayOfLastMonth->toDateString(),
            'clock_in' => '10:00:00',
            'clock_out' => '19:00:00',
        ]);

        // 選択ユーザーの月次勤怠一覧画面を今月で開く
        $response = $this->get(route('admin.staff_attendance', ['id' => $user->id, 'month' => $firstDayOfThisMonth->format('Y/m')]));
        $response->assertStatus(200);

        // 画面に「前月」ボタンが存在することを確認
        $prevMonthUrl = route('admin.staff_attendance', ['id' => $user->id, 'month' => $firstDayOfLastMonth->format('Y/m')]);
        $response->assertSee($prevMonthUrl);

        $responsePrev = $this->get($prevMonthUrl);
        $responsePrev->assertStatus(200);

        $responsePrev->assertSee($attendanceLastMonth->date->format('m/d'));
        $responsePrev->assertSee(substr($attendanceLastMonth->clock_in, 0, 5));
        $responsePrev->assertSee(substr($attendanceLastMonth->clock_out, 0, 5));

        $responsePrev->assertDontSee($attendanceThisMonth->date->format('m/d'));
    }

    /**
     * @test
     * 「翌月」を押下した時に表示月の前月の情報が表示される
     */
    public function view_next_month_attendance()
    {
        /** @var \App\Models\Admin $admin */
        $admin = Admin::factory()->create();
        $this->actingAs($admin, 'admin');

        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
        ]);

        // 基点となる月と翌月の勤怠情報を作成
        $firstDayOfMonth = Carbon::create(2025, 8, 1);
        $firstDayOfNextMonth = $firstDayOfMonth->copy()->addMonth();

        Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => $firstDayOfMonth->toDateString(),
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => $firstDayOfNextMonth->toDateString(),
            'clock_in' => '09:30:00',
            'clock_out' => '18:30:00',
        ]);

        // 選択したユーザーの月次勤怠一覧画面を基点の月で開く
        $response = $this->get(route('admin.staff_attendance', [
            'id' => $user->id,
            'month' => $firstDayOfMonth->format('Y/m'),
        ]));

        $response->assertStatus(200);

        // 画面に「翌月」ボタンが存在することを確認
        $nextMonthUrl = route('admin.staff_attendance', [
            'id' => $user->id,
            'month' => $firstDayOfNextMonth->format('Y/m'),
        ]);
        $response->assertSee($nextMonthUrl);

        // 翌月の勤怠情報ページに遷移して確認
        $responseNext = $this->get($nextMonthUrl);
        $responseNext->assertStatus(200)
                     ->assertSee($user->name)
                     ->assertSee('09:30') // clock_in
                     ->assertSee('18:30'); // clock_out
    }

    /**
     * @test
     * 「詳細」を押下すると、その日の勤怠詳細画面に遷移する
     */
    public function attendance_detail_from_monthly_view()
    {
        /** @var \App\Models\Admin $admin */
        $admin = Admin::factory()->create();
        $this->actingAs($admin, 'admin');

        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
        ]);

        $today = Carbon::today();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => $today->toDateString(),
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $response = $this->get(route('admin.staff_attendance', [
            'id' => $user->id,
            'month' => $today->format('Y/m'),
        ]));
        $response->assertStatus(200);

        $detailUrl = route('admin.attendance.show', ['id' => $attendance->id]);
        $response->assertSee('<button', false); 
        $response->assertSee('詳細');

        // 勤怠詳細画面に遷移して勤怠情報を確認
        $responseDetail = $this->get($detailUrl);
        $responseDetail->assertStatus(200)
                        ->assertSee('勤怠詳細')
                        ->assertSee($user->name)
                        ->assertSee('09:00')
                        ->assertSee('18:00');
    }
}
