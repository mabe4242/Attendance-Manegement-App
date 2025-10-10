<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RequestStatus;
use App\Enums\TableHeaders;
use App\Http\Controllers\Controller;
use App\Models\AttendanceRequest;
use App\Services\AttendanceRequestService;
use App\Traits\HandlesTransaction;
use Illuminate\Http\Request;

class AttendanceRequestController extends Controller
{
    use HandlesTransaction;

    public function index(Request $request)
    {
        $status = $request->query('status', RequestStatus::PENDING);
        $attendanceRequests = AttendanceRequest::with('user')
            ->status($status)->latestOrder()->get();
        $headers = TableHeaders::requests();

        return view('admin.request_index', compact('attendanceRequests', 'status', 'headers'));
    }

    public function show($id)
    {
        $attendanceRequest = AttendanceRequest::with(['user', 'breakRequests'])->findOrFail($id);
        $attendance = $attendanceRequest->attendance()->with('breaks')->first();
        $breaks = $attendanceRequest->breakRequests ?? $attendance->breaks;

        return view('admin.approve', compact('attendanceRequest', 'attendance', 'breaks'));
    }

    public function approve(AttendanceRequest $attendance_correct_request)
    {
        return $this->handleTransaction(function () use ($attendance_correct_request) {
            AttendanceRequestService::markApproved($attendance_correct_request, auth()->id());
            AttendanceRequestService::updateAttendanceFromRequest($attendance_correct_request->attendance, 
                $attendance_correct_request);
            AttendanceRequestService::updateBreaksFromRequest($attendance_correct_request->attendance, 
                $attendance_correct_request->breakRequests);

            return redirect()->route('admin.request', $attendance_correct_request->id);
        }, '申請の承認に失敗しました。');
    }
}
