<?php

namespace App\Http\Controllers;

use App\Enums\RequestStatus;
use App\Models\Attendance;
use App\Models\AttendanceRequest;
use App\Traits\HandlesTransaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AttendanceRequestController extends Controller
{
    use HandlesTransaction;

    public function show($id)
    {
        $attendance = Attendance::with('breaks')->findOrFail($id);
        $attendanceRequest = AttendanceRequest::where('attendance_id', $id)->latest()->first();
        $breaks = $attendanceRequest ? $attendanceRequest->breakRequests : $attendance->breaks;

        return view('user.attendance_detail', compact('attendance', 'attendanceRequest', 'breaks'));
    }

    public function index(Request $request)
    {
        $status = $request->input('status', RequestStatus::PENDING);
        $query = AttendanceRequest::where('user_id', Auth::id())->where('status', $status);
        $attendanceRequests = $query->latest()->get();

        return view('user.request_index', compact('attendanceRequests', 'status'));
    }

    public function detailOrCreate($date)
    {
        $userId = Auth::id();
        $dateCarbon = Carbon::parse($date);
        $attendance = Attendance::firstOrCreate(
            ['user_id' => $userId, 'date' => $dateCarbon->toDateString()],
            ['status' => 0]
        );

        return redirect()->route('attendance.detail', ['id' => $attendance->id]);
    }

    public function store(Request $request, $attendanceId)
    {
        $attendance = Attendance::with('breaks')->findOrFail($attendanceId);
        $attendanceRequest = $this->handleTransaction(function () use ($request, $attendance) {
            return AttendanceRequest::createWithBreaks($request->all(), $attendance);
        }, '勤怠修正申請の登録に失敗しました。');

        if ($attendanceRequest instanceof \Illuminate\Http\RedirectResponse) {
            return $attendanceRequest;
        }

        return redirect()->route('attendance.index');
    }
}
