<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class UserAttendanceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * 現在の日時情報がUIと同じ形式で出力されている
     */
    public function attendance_clock_test()
    {
        Carbon::setTestNow(Carbon::now());

        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'email_verified_at' => Carbon::now(),
        ]);

        $this->actingAs($user);
        $response = $this->get(route('attendance.create'));
        $response->assertStatus(200);

        $response->assertSee('new Date()', false);
        $response->assertSee('const weekdays = ["日", "月", "火", "水", "木", "金", "土"];', false);

        //「○年○月○日(○)」形式の文字列を構成していることを確認
        $response->assertSee('getFullYear()', false)
                 ->assertSee('getMonth()', false)
                 ->assertSee('getDate()', false)
                 ->assertSee('weekdays[now.getDay()]', false);

        $response->assertSee('padStart(2, \'0\')', false);
        $response->assertSee('const h = String(now.getHours()).padStart(2, \'0\');', false);
        $response->assertSee('const m = String(now.getMinutes()).padStart(2, \'0\');', false);
        $response->assertSee('const timeStr = `${h}:${m}`', false);
        $response->assertSee('document.getElementById(\'attendance-time\').textContent = timeStr;', false);
    }
}
