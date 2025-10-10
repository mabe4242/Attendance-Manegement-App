<?php

namespace Tests\Feature;

use App\Enums\RequestStatus;
use App\Models\Admin;
use App\Models\Attendance;
use App\Models\AttendanceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceRequestTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * 出勤時間が退勤時間より後になっている場合、エラーメッセージが表示される
     */
    public function error_when_clock_in_is_after_clock_out()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'clock_in' => '2025-09-20 09:00:00',
            'clock_out' => '2025-09-20 18:00:00',
        ]);

        $this->actingAs($user);

        // 勤怠修正申請（出勤時間を退勤時間より後にする）
        $response = $this->post(route('attendance_request.store', $attendance->id), [
            'year' => '2025',
            'month_day' => '09-20',
            'clock_in' => '20:00',
            'clock_out' => '18:00',
            'reason' => 'テスト',
        ]);

        $response->assertStatus(302);

        // バリデーションエラーを確認
        $response->assertSessionHasErrors([
            'clock_in' => '出勤時間もしくは退勤時間が不適切な値です',
        ]);
    }

    /**
     * @test
     * 休憩開始時間が退勤時間より後になっている場合、エラーメッセージが表示される
     */
    public function error_when_break_start_after_clock_out()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'clock_in' => '2025-10-01 09:00:00',
            'clock_out' => '2025-10-01 18:00:00',
        ]);

        $this->actingAs($user);

        // 勤怠修正申請POST（休憩開始を退勤より後に設定）
        $response = $this->post(route('attendance_request.store', $attendance->id), [
            'year' => '2025',
            'month_day' => '10-01',
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'breaks' => [
                [
                    'break_start' => '19:00',
                    'break_end' => '19:30',
                ]
            ],
            'reason' => 'テスト',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors([
            'breaks.0.break_start' => '休憩時間が不適切な値です',
        ]);
    }

    /**
     * @test
     * 休憩終了時間が退勤時間より後になっている場合、エラーメッセージが表示される
     */
    public function error_when_break_end_after_clock_out()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'clock_in' => '2025-10-01 09:00:00',
            'clock_out' => '2025-10-01 18:00:00',
        ]);

        $this->actingAs($user);

        // 勤怠修正申請POST（休憩終了を退勤より後に設定）
        $response = $this->post(route('attendance_request.store', $attendance->id), [
            'year' => '2025',
            'month_day' => '10-01',
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'breaks' => [
                [
                    'break_start' => '17:30',
                    'break_end' => '19:00',
                ]
            ],
            'reason' => 'テスト',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors([
            'breaks.0.break_end' => '休憩時間もしくは退勤時間が不適切な値です',
        ]);
    }

    /**
     * @test
     * 備考欄が未入力の場合のエラーメッセージが表示される
     */
    public function error_when_reason_is_empty()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'clock_in' => '2025-10-01 09:00:00',
            'clock_out' => '2025-10-01 18:00:00',
        ]);

        $this->actingAs($user);

        // 勤怠修正申請POST（備考未入力）
        $response = $this->post(route('attendance_request.store', $attendance->id), [
            'year' => '2025',
            'month_day' => '10-01',
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'breaks' => [
                [
                    'break_start' => '12:00',
                    'break_end' => '13:00',
                ]
            ],
            'reason' => '',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors([
            'reason' => '備考を記入してください。',
        ]);
    }

    /**
     * @test
     * 修正申請処理が実行される
     */
    public function submit_request_and_admin_can_view_it()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create();
        /** @var \App\Models\Admin $admin */
        $admin = Admin::factory()->create();

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2025-10-01',
            'clock_in' => '2025-10-01 09:00:00',
            'clock_out' => '2025-10-01 18:00:00',
        ]);

        // 一般ユーザーとして修正申請を行う
        $this->actingAs($user);
        $response = $this->post(route('attendance_request.store', $attendance->id), [
            'year' => '2025',
            'month_day' => '10-01',
            'clock_in' => '09:30',
            'clock_out' => '18:30',
            'breaks' => [
                [
                    'break_start' => '12:00',
                    'break_end' => '13:00',
                ]
            ],
            'reason' => '修正テスト',
        ]);

        $response->assertStatus(302);
        $response->assertRedirect(route('attendance.detail', ['id' => $attendance->id]));

        $this->assertDatabaseHas('attendance_requests', [
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'reason' => '修正テスト',
        ]);

        $attendanceRequest = AttendanceRequest::where('user_id', $user->id)->firstOrFail();

        // 管理者の承認画面の確認
        $this->actingAs($admin, 'admin');
        $approveResponse = $this->get(route('admin.request', $attendanceRequest->id));
        $approveResponse->assertStatus(200);
        $approveResponse->assertSee('修正テスト');

        // 管理者の申請一覧画面の確認
        $indexResponse = $this->get(route('admin.attendance_requests.index'));
        $indexResponse->assertStatus(200);
        $indexResponse->assertSee($user->name);
        $indexResponse->assertSee('修正テスト');
    }

    /**
     * @test
     * 「承認待ち」にログインユーザーが行った申請が全て表示されていること
     */
    public function pending_requests_on_index()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create();
        /** @var \App\Models\User $otherUser */
        $otherUser = User::factory()->create();

        // ログインユーザーの勤怠データ
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'clock_in' => '2025-10-01 09:00:00',
            'clock_out' => '2025-10-01 18:00:00',
        ]);

        // ログインユーザーの申請を2件作成
        $requests = AttendanceRequest::factory()->count(2)->create([
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'status' => RequestStatus::PENDING,
            'reason' => 'テスト',
        ]);

        // 他ユーザーの申請
        AttendanceRequest::factory()->create([
            'user_id' => $otherUser->id,
            'attendance_id' => $attendance->id,
            'status' => RequestStatus::PENDING,
            'reason' => '他人の申請です',
        ]);

        $this->actingAs($user);
        $response = $this->get(route('attendance_requests.index'));
        $response->assertStatus(200);

        // ログインユーザー自身の申請がすべて表示されていることを確認
        foreach ($requests as $request) {
            $response->assertSee('承認待ち');
            $response->assertSee($user->name);
            $response->assertSee($request->reason);
            $response->assertSee($request->request_date->format('Y/m/d'));
        }

        $response->assertDontSee('他人の申請');
    }

    /**
     * @test
     * 「承認済み」に管理者が承認した修正申請が全て表示されている
     */
    public function approved_requests_on_index()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create();
        /** @var \App\Models\Admin $admin */
        $admin = Admin::factory()->create();

        $attendance1 = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2025-10-01',
            'clock_in' => '2025-10-01 09:00:00',
            'clock_out' => '2025-10-01 18:00:00',
        ]);
        $attendance2 = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2025-10-02',
            'clock_in' => '2025-10-02 09:00:00',
            'clock_out' => '2025-10-02 18:00:00',
        ]);

        $this->actingAs($user);

        // ユーザーが2件の修正申請を作成
        $this->post(route('attendance_request.store', $attendance1->id), [
            'year' => '2025',
            'month_day' => '10-01',
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'breaks' => [
                ['break_start' => '12:00', 'break_end' => '12:30'],
            ],
            'reason' => 'テスト1',
        ]);
        $this->post(route('attendance_request.store', $attendance2->id), [
            'year' => '2025',
            'month_day' => '10-02',
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'breaks' => [
                ['break_start' => '13:00', 'break_end' => '13:30'],
            ],
            'reason' => 'テスト2',
        ]);

        $request1 = $attendance1->attendanceRequests()->latest()->first();
        $request2 = $attendance2->attendanceRequests()->latest()->first();

        // 管理者が承認処理を実行
        $this->actingAs($admin, 'admin');
        $this->post(route('admin.approve', $request1->id));
        $this->post(route('admin.approve', $request2->id));

        // ステータスが承認済みに更新されていることを確認
        $this->assertDatabaseHas('attendance_requests', [
            'id' => $request1->id,
            'status' => RequestStatus::APPROVED,
        ]);
        $this->assertDatabaseHas('attendance_requests', [
            'id' => $request2->id,
            'status' => RequestStatus::APPROVED,
        ]);

        $this->actingAs($user);
        $response = $this->get(route('attendance_requests.index', ['status' => RequestStatus::APPROVED]));
        $response->assertStatus(200);

        // 承認済みの申請内容が全て画面に表示されていることを確認
        $response->assertSee('承認済み');
        $response->assertSee('テスト1');
        $response->assertSee('テスト2');
    }

    /**
     * @test
     * 各申請の「詳細」を押下すると勤怠詳細画面に遷移する
     */
    public function attendance_detail_from_request_list()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'clock_in' => '2025-10-01 09:00:00',
            'clock_out' => '2025-10-01 18:00:00',
        ]);

        $this->actingAs($user);

        // 勤怠修正申請
        $response = $this->post(route('attendance_request.store', $attendance->id), [
            'year' => '2025',
            'month_day' => '10-01',
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'breaks' => [
                [
                    'break_start' => '12:00',
                    'break_end' => '12:30',
                ]
            ],
            'reason' => 'テスト',
        ]);

        // 申請一覧画面を開く
        $listResponse = $this->get(route('attendance_requests.index'));
        $listResponse->assertStatus(200);

        $attendanceRequest = $attendance->attendanceRequests()->latest()->first();

        $detailUrl = route('attendance.detail', [
            'id' => $attendanceRequest->attendance_id,
            'request_id' => $attendanceRequest->id,
            'from' => 'request_list'
        ]);
        $listResponse->assertSee($detailUrl);

        // 「詳細」から勤怠詳細画面に遷移
        $detailResponse = $this->get($detailUrl);
        $detailResponse->assertStatus(200);
        $detailResponse->assertSee($user->name);
        $detailResponse->assertSee('勤怠詳細');
    }
}
