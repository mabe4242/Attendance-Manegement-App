<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RequestStatus;
use App\Enums\TableHeaders;
use App\Http\Controllers\Controller;
use App\Models\AttendanceRequest;
use Illuminate\Http\Request;

class AttendanceRequestController extends Controller
{
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
}
