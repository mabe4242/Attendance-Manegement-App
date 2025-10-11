<?php

namespace Tests\Feature;

use App\Enums\RequestStatus;
use App\Models\Admin;
use App\Models\Attendance;
use App\Models\AttendanceRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminApproveTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * 承認待ちの修正申請が全て表示されている
     */
    public function admin_can_view_pending_requests()
    {
        /** @var \App\Models\Admin $admin */
        $admin = Admin::factory()->create();
        $this->actingAs($admin, 'admin');
        $this->actingAs($admin, 'admin')->withSession(['last_guard' => 'admin']);

        $requests = AttendanceRequest::factory()->count(3)->create([
            'status' => RequestStatus::PENDING,
        ]);

        $response = $this->get(route('attendance_requests.index', [
            'status' => RequestStatus::PENDING,
        ]));
        $response->assertStatus(200);

        // 各修正申請が表示されていることを確認
        foreach ($requests as $request) {
            $response->assertSee($request->user->name);
            $response->assertSee($request->request_date->format('Y/m/d'));
            $response->assertSee(mb_substr($request->reason, 0, 6));
        }
    }

    /**
     * @test
     * 承認済みの修正申請が全て表示されている
     */
    public function admin_can_view_approved_requests()
    {
        /** @var \App\Models\Admin $admin */
        $admin = Admin::factory()->create();

        $requests = AttendanceRequest::factory()
            ->count(3)
            ->for(User::factory())
            ->for(Attendance::factory())
            ->create(['status' => RequestStatus::APPROVED]);

        $this->actingAs($admin, 'admin')->withSession(['last_guard' => 'admin']);
        $response = $this->get(route('attendance_requests.index', [
            'status' => RequestStatus::APPROVED,
        ]));
        $response->assertStatus(200);

        // すべての承認済みデータが表示されていることを確認
        foreach ($requests as $request) {
            $response->assertSee($request->user->name);
            $response->assertSee(mb_substr($request->reason, 0, 6));
            $response->assertSee($request->request_date->format('Y/m/d'));
        }
    }

    /**
     * @test
     * 修正申請の詳細内容が正しく表示されている
     */
    public function correction_request_detail()
    {
        /** @var \App\Models\Admin $admin */
        $admin = Admin::factory()->create();
        /** @var \App\Models\User $user */
        $user = User::factory()->create();

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $request = AttendanceRequest::factory()->create([
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'request_date' => Carbon::create(2025, 10, 1),
            'clock_in' => '09:30:00',
            'clock_out' => '18:15:00',
            'status' => RequestStatus::PENDING,
            'reason' => '修正テスト',
        ]);

        $this->actingAs($admin, 'admin')->withSession(['last_guard' => 'admin']);
        $response = $this->get(route('attendance_requests.index', [
            'status' => RequestStatus::PENDING,
        ]));

        $response->assertStatus(200);
        $response->assertSee($user->name);
        $response->assertSee($request->reason);

        $detailResponse = $this->get(route('admin.request', 
            ['attendance_correct_request' => $request->id]));
        $detailResponse->assertStatus(200);

        // 詳細内容が正しく表示されていることを確認
        $detailResponse->assertSee($user->name);
        $detailResponse->assertSee('2025年');
        $detailResponse->assertSee('10月1日');
        $detailResponse->assertSee('09:30');      // 修正後の出勤時刻
        $detailResponse->assertSee('18:15');      // 修正後の退勤時刻
        $detailResponse->assertSee('修正テスト');   //備考
    }

    /**
     * @test
     * 修正申請の承認処理が正しく行われる
     */
    public function correction_request_approved_and_attendance_updated()
    {
        /** @var \App\Models\Admin $admin */
        $admin = Admin::factory()->create();
        /** @var \App\Models\User $user */
        $user = User::factory()->create();

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $request = AttendanceRequest::factory()->create([
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'request_date' => Carbon::create(2025, 10, 1),
            'clock_in' => '09:30:00',
            'clock_out' => '18:30:00',
            'status' => RequestStatus::PENDING,
            'reason' => '修正テスト',
        ]);

        $this->actingAs($admin, 'admin');
        $detailResponse = $this->get(route('admin.request', [
            'attendance_correct_request' => $request->id,
        ]));
        $detailResponse->assertStatus(200);
        $detailResponse->assertSee('承認');

        // 「承認」ボタンを押す
        $approveResponse = $this->post(route('admin.approve', [
            'attendance_correct_request' => $request->id,
        ]));
        $approveResponse->assertStatus(302);

        $request->refresh();
        $attendance->refresh();

        // 勤怠データが修正後の値に更新されている
        $this->assertEquals(RequestStatus::APPROVED, $request->status);
        $this->assertEquals('09:30:00', $attendance->clock_in->format('H:i:s'));
        $this->assertEquals('18:30:00', $attendance->clock_out->format('H:i:s'));
    }
}
