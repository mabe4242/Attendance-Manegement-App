<?php

namespace App\Http\Controllers;

use App\Enums\RequestStatus;
use App\Models\Attendance;
use App\Models\AttendanceRequest;
use App\Models\BreakRequest;
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
            $clockIn = $request->clock_in ? (clone $attendance->date)->setTimeFromTimeString($request->clock_in) : null;
            $clockOut = $request->clock_out ? (clone $attendance->date)->setTimeFromTimeString($request->clock_out) : null;

            $attendanceRequest = AttendanceRequest::create([
                'user_id' => Auth::id(),
                'attendance_id' => $attendance->id,
                'request_date' => $attendance->date,
                'clock_in' => $clockIn,
                'clock_out' => $clockOut,
                'status' => RequestStatus::PENDING,
                'reason' => $request->reason,
            ]);

            $breaksInput = $request->input('breaks', []);
            foreach ($breaksInput as $index => $breakData) {
                $breakStart = $breakData['break_start'] ?? null;
                $breakEnd = $breakData['break_end'] ?? null;

                if ($breakStart || $breakEnd) {
                    BreakRequest::create([
                        'attendance_request_id' => $attendanceRequest->id,
                        'break_id' => $attendance->breaks[$index]->id ?? null,
                        'break_start' => $breakStart ? (clone $attendance->date)->setTimeFromTimeString($breakStart) : null,
                        'break_end' => $breakEnd ? (clone $attendance->date)->setTimeFromTimeString($breakEnd) : null,
                    ]);
                }
            }

            return $attendanceRequest;
        }, '勤怠修正申請の登録に失敗しました。');

        // もし handleTransaction がエラーを返した場合は RedirectResponse なのでそのまま return
        if ($attendanceRequest instanceof \Illuminate\Http\RedirectResponse) {
            return $attendanceRequest;
        }

        return redirect()->route('attendance.index');
    }
}
