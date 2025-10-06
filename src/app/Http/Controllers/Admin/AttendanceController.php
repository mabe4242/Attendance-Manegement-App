<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AttendanceStatus;
use App\Enums\TableHeaders;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateRequest;
use App\Models\Attendance;
use App\Models\User;
use App\Services\AttendanceFormatter;
use App\Services\AttendanceService;
use App\Services\CarbonCalc;
use App\Traits\HandlesTransaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AttendanceController extends Controller
{
    use HandlesTransaction;

    public function index(Request $request)
    {
        $date = $request->query('date') ? Carbon::parse($request->query('date'))->startOfDay() : Carbon::today();
        $attendances = AttendanceService::getDailyAttendances($date);
        $attendances = AttendanceFormatter::formatDay($attendances);

        $prevUrl = route('admin.attendance.index', ['date' => $date->copy()->subDay()->toDateString()]);
        $nextUrl = route('admin.attendance.index', ['date' => $date->copy()->addDay()->toDateString()]);
        $headers = TableHeaders::attendanceDaily();

        return view('admin.attendance_index', compact('date', 'prevUrl', 'nextUrl', 'attendances', 'headers'));
    }

    public function staffAttendances(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $month = $request->query('month', Carbon::now()->format('Y/m'));
        $startOfMonth = Carbon::createFromFormat('Y/m', $month)->startOfMonth();
        $endOfMonth   = Carbon::createFromFormat('Y/m', $month)->endOfMonth();

        $attendances = AttendanceService::getMonthlyAttendances($user->id, $startOfMonth, $endOfMonth);
        $attendances = AttendanceFormatter::formatMonth($attendances);

        $months = CarbonCalc::getMonths($month);
        $prevMonthUrl = route('admin.staff_attendance', ['id' => $user->id, 'month' => $months['prevMonth']]);
        $nextMonthUrl = route('admin.staff_attendance', ['id' => $user->id, 'month' => $months['nextMonth']]);
        $headers = TableHeaders::attendanceMonthly();

        return view('admin.staff_attendances', compact('user', 'attendances', 'month', 'prevMonthUrl', 
            'nextMonthUrl', 'headers'));
    }

    public function show($id)
    {
        $attendance = Attendance::with(['breaks', 'user'])->findOrFail($id);
        $breaks = $attendance->breaks;

        return view('admin.show', compact('attendance', 'breaks'));
    }

    public function detailOrCreate(User $user, $date)
    {
        $userId = $user->id;;
        $dateCarbon = Carbon::parse($date);
        $attendance = Attendance::firstOrCreate(
            ['user_id' => $userId, 'date' => $dateCarbon->toDateString()],
            ['status' => AttendanceStatus::OFF]
        );

        return redirect()->route('admin.attendance.show', ['id' => $attendance->id]);
    }

    public function update(UpdateRequest $request, $id)
    {
        return $this->handleTransaction(function () use ($request, $id) {
            $attendance = Attendance::with('breaks')->findOrFail($id);
            AttendanceService::updateAttendance($attendance, $request->all());
            AttendanceService::updateBreaks($attendance, $request->breaks ?? [], 
                $request->year, $request->month_day);

            return redirect()->route('admin.attendance.index');
        }, '勤怠の更新に失敗しました。');
    }

    public function export(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $month = $request->query('month', Carbon::now()->format('Y/m'));
        $start = Carbon::createFromFormat('Y/m', $month)->startOfMonth();
        $end   = Carbon::createFromFormat('Y/m', $month)->endOfMonth();

        $fileName = "{$user->user_name}_勤怠一覧_{$start->format('Ym')}.csv";
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$fileName\"",
        ];

        return response()->stream(AttendanceService::exportMonthly($user, $start, $end), Response::HTTP_OK, $headers);
    }
}
