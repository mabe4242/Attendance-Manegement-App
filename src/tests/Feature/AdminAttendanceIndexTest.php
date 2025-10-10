<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Attendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class AdminAttendanceIndexTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * その日になされた全ユーザーの勤怠情報が正確に確認できる
     */
    public function admin_can_view_all_attendance()
    {
        /** @var \App\Models\Admin $admin */
        $admin = Admin::factory()->create();

        /** @var \App\Models\User $user1 */
        /** @var \App\Models\User $user2 */
        $user1 = User::factory()->create(['name' => 'テストユーザーA']);
        $user2 = User::factory()->create(['name' => 'テストユーザーB']);

        $today = Carbon::today();
        Attendance::factory()->create([
            'user_id' => $user1->id,
            'date' => $today,
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        Attendance::factory()->create([
            'user_id' => $user2->id,
            'date' => $today,
            'clock_in' => '10:00:00',
            'clock_out' => '19:00:00',
        ]);

        $this->actingAs($admin, 'admin');
        $response = $this->get(route('admin.attendance.index'));

        $response->assertStatus(200)
                 ->assertSee('テストユーザーA')
                 ->assertSee('09:00')
                 ->assertSee('18:00')
                 ->assertSee('テストユーザーB')
                 ->assertSee('10:00')
                 ->assertSee('19:00')
                 ->assertSee($today->format('n月j日'));
    }

    /**
     * @test
     * 遷移した際に現在の日付が表示される
     */
    public function current_date_test()
    {
        /** @var \App\Models\Admin $admin */
        $admin = Admin::factory()->create();

        $this->actingAs($admin, 'admin');
        $today = now()->format('Y年n月j日');
        $response = $this->get(route('admin.attendance.index'));

        $response->assertStatus(200)
                ->assertSee($today);
    }

    /**
     * @test
     * 「前日」を押下した時に前の日の勤怠情報が表示される
     */
    public function previous_day_attendance()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'name' => 'テストユーザー',
        ]);

        $yesterday = Carbon::yesterday()->startOfDay();
        $today = Carbon::today()->startOfDay();
        Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => $yesterday,
            'clock_in' => $yesterday->copy()->setTime(9, 0),
            'clock_out' => $yesterday->copy()->setTime(18, 0),
        ]);

        /** @var \App\Models\Admin $admin */
        $admin = Admin::factory()->create();
        $this->actingAs($admin, 'admin');

        $response = $this->get(route('admin.attendance.index', [
            'date' => $today->toDateString(),
        ]));
        $response->assertStatus(200)
                ->assertSee($today->isoFormat('YYYY年M月D日'));

        $prevDayUrl = route('admin.attendance.index', ['date' => $yesterday->toDateString()]);
        $response->assertSee($prevDayUrl);

        $response = $this->get(route('admin.attendance.index', [
            'date' => $yesterday->toDateString(),
        ]));

        $response->assertStatus(200)
                ->assertSee($yesterday->isoFormat('YYYY年M月D日'))
                ->assertSee('テストユーザー')
                ->assertSee('09:00')
                ->assertSee('18:00');
    }

    /**
     * @test
     * 「翌日」を押下した時に次の日の勤怠情報が表示される
     */
    public function next_day_attendance()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'name' => 'テストユーザー',
        ]);

        $baseDate = Carbon::today()->subDays(3)->startOfDay();
        $nextDate = $baseDate->copy()->addDay();

        Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => $nextDate,
            'clock_in' => $nextDate->copy()->setTime(9, 0),
            'clock_out' => $nextDate->copy()->setTime(18, 0),
        ]);

        /** @var \App\Models\Admin $admin */
        $admin = Admin::factory()->create();
        $this->actingAs($admin, 'admin');

        //まずは基点となる日の勤怠一覧画面にアクセス
        $response = $this->get(route('admin.attendance.index', [
            'date' => $baseDate->toDateString(),
        ]));
        $response->assertStatus(200);

        $nextDayUrl = route('admin.attendance.index', ['date' => $nextDate->toDateString()]);
        $response->assertSee($nextDayUrl);

        // 基点の翌日の勤怠一覧画面にアクセスし、データが表示されることを確認
        $responseNext = $this->get(route('admin.attendance.index', [
            'date' => $nextDate->toDateString(),
        ]));
        $responseNext->assertStatus(200)
            ->assertSee($nextDate->isoFormat('YYYY年M月D日'))
            ->assertSee('テストユーザー')
            ->assertSee('09:00')
            ->assertSee('18:00');
    }
}
