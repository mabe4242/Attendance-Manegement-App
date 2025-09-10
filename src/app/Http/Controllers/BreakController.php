<?php

namespace App\Http\Controllers;

use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use App\Models\UserBreak;
use Illuminate\Support\Facades\DB;

class BreakController extends Controller
{
    public function start($id)
    {
        return DB::transaction(function () use ($id) {
            $attendance = Attendance::lockForUpdate()->findOrFail($id);

            if ($attendance->status === AttendanceStatus::BREAK) {
                return back();
            }

            UserBreak::create([
                'attendance_id' => $attendance->id,
                'break_start' => now(),
            ]);

            $attendance->update([
                'status' => AttendanceStatus::BREAK,
            ]);

            return back();
        });
    }

    public function end($id)
    {
        return DB::transaction(function () use ($id) {
            $attendance = Attendance::lockForUpdate()->findOrFail($id);

            $break = UserBreak::where('attendance_id', $attendance->id)
                ->whereNull('break_end')
                ->latest('break_start')
                ->firstOrFail();

            $break->update([
                'break_end' => now(),
            ]);

            $attendance->update([
                'status' => AttendanceStatus::WORKING,
            ]);

            return back();
        });
    }
}
