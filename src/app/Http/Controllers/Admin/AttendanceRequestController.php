<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRequest;
use App\Enums\RequestStatus;
use Illuminate\Http\Request;

class AttendanceRequestController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', RequestStatus::PENDING);
        $attendanceRequests = AttendanceRequest::with('user')
            ->status($status)->latestOrder()->get();

        return view('admin.request_index', compact('attendanceRequests', 'status'));
    }

    public function show($id)
    {
        $attendanceRequest = AttendanceRequest::with(['user', 'breakRequests'])->findOrFail($id);
        $attendance = $attendanceRequest->attendance()->with('breaks')->first();
        $breaks = $attendanceRequest->breakRequests ?? $attendance->breaks;

        return view('admin.approve', compact('attendanceRequest', 'attendance', 'breaks'));
    }
}
