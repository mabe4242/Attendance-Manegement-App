<?php

namespace Database\Seeders;

use App\Enums\AttendanceStatus;
use App\Models\Admin;
use App\Models\Attendance;
use App\Models\AttendanceRequest;
use App\Models\BreakRequest;
use App\Models\User;
use App\Models\UserBreak;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AttendanceDataSeeder extends Seeder
{
    public function run()
    {
        $this->call([
            UsersTableSeeder::class,
            AdminsTableSeeder::class,
        ]);

        $users = User::whereIn('email', [
            'user1@example.com',
            'user2@example.com',
            'user3@example.com',
        ])->get();

        $admin = Admin::where('email', 'test@example.com')->first();
        $reasons = ['打刻ミスのため','電車遅延のため','体調不良のため'];

        foreach ($users as $user) {
            $attendances = [];

            // 過去30日分の勤怠（本日は除く、昨日から30日前）
            for ($i = 1; $i <= 30; $i++) {
                $date = Carbon::now()->subDays($i)->format('Y-m-d');

                $attendance = Attendance::factory()->create([
                    'user_id' => $user->id,
                    'date' => $date,
                    'clock_in' => Carbon::parse("$date 09:00:00"),
                    'clock_out' => Carbon::parse("$date 18:00:00"),
                    'status' => AttendanceStatus::FINISHED,
                ]);

                UserBreak::factory()->create([
                    'attendance_id' => $attendance->id,
                    'break_start' => Carbon::parse("$date 12:00:00"),
                    'break_end' => Carbon::parse("$date 13:00:00"),
                ]);

                $attendances[] = $attendance;
            }

            $attendanceIds = collect($attendances)->pluck('id')->toArray();

            // 勤怠申請6件（上3件は承認済み）
            for ($i = 1; $i <= 6; $i++) {
                $attendanceId = $attendanceIds[array_rand($attendanceIds)];
                $status = $i <= 3 ? 1 : 0;
                $adminId = $status === 1 ? $admin->id : null;

                $date = Attendance::find($attendanceId)->date;

                $request = AttendanceRequest::factory()->create([
                    'user_id' => $user->id,
                    'attendance_id' => $attendanceId,
                    'admin_id' => $adminId,
                    'status' => $status,
                    'reason' => $reasons[array_rand($reasons)],
                    'request_date' => $date,
                    'clock_in' => Carbon::parse($date)->setTime(10, 0, 0),
                    'clock_out' => Carbon::parse($date)->setTime(18, 0, 0),
                ]);

                $breakId = UserBreak::where('attendance_id', $attendanceId)->value('id');

                BreakRequest::factory()->for($request)->create([
                    'break_id' => $breakId,
                    'break_start' => Carbon::parse($date)->setTime(13, 0, 0),
                    'break_end' => Carbon::parse($date)->setTime(14, 0, 0),
                ]);
            }
        }
    }
}