<?php

namespace App\Services;

use App\Enums\RequestStatus;
use App\Models\AttendanceRequest;

class AttendanceRequestService
{
    public static function markApproved(AttendanceRequest $attendanceRequest, int $adminId)
    {
        $attendanceRequest->update([
            'status'   => RequestStatus::APPROVED,
            'admin_id' => $adminId,
        ]);
    }

    public static function updateAttendanceFromRequest($attendance, AttendanceRequest $attendanceRequest)
    {
        $attendance->update([
            'clock_in'  => $attendanceRequest->clock_in,
            'clock_out' => $attendanceRequest->clock_out,
            'reason'    => $attendanceRequest->reason,
        ]);
    }

    public static function updateBreaksFromRequest($attendance, $breakRequests)
    {
        foreach ($breakRequests as $breakRequest) {
            if ($breakRequest->break_id) {
                $break = $attendance->breaks()->find($breakRequest->break_id);
                if ($break) {
                    $break->update([
                        'break_start' => $breakRequest->break_start,
                        'break_end'   => $breakRequest->break_end,
                    ]);
                }
            } else {
                $attendance->breaks()->create([
                    'break_start' => $breakRequest->break_start,
                    'break_end'   => $breakRequest->break_end,
                ]);
            }
        }
    }
}
