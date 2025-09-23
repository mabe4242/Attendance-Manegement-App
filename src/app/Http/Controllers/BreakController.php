<?php

namespace App\Http\Controllers;

use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use App\Models\UserBreak;
use App\Traits\HandlesTransaction;

class BreakController extends Controller
{
    use HandlesTransaction;

    public function start($id)
    {
        return $this->handleTransaction(function () use ($id) {
            $attendance = Attendance::lockForUpdate()->findOrFail($id);

            if ($attendance->status === AttendanceStatus::BREAK) {
                return back();
            }

            UserBreak::create([
                'attendance_id' => $attendance->id,
                'break_start' => now(),
            ]);

            $attendance->update(['status' => AttendanceStatus::BREAK]);

            return back();
        }, '休憩開始処理に失敗しました。');
    }

    public function end($id)
    {
        return $this->handleTransaction(function () use ($id) {
            $attendance = Attendance::lockForUpdate()->findOrFail($id);

            $break = UserBreak::where('attendance_id', $attendance->id)
                ->whereNull('break_end')
                ->latest('break_start')
                ->firstOrFail();

            $break->update(['break_end' => now()]);
            $attendance->update(['status' => AttendanceStatus::WORKING]);

            return back();
        }, '休憩終了処理に失敗しました。');
    }
}
