@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/user/attendance_index.css') }}">
@endsection

@section('content')
    <x-user_header />
    <div class="content">
        <div class="attendance__content">
            <div class="attendance__wrapper">
                <p class="attendance__title">勤怠一覧</p>
                <x-table-menu :prevUrl="$prevMonthUrl" :main="$month" :nextUrl="$nextMonthUrl" prev="前月" next="翌月"/>
                <x-table :headers="['日付', '出勤', '退勤', '休憩', '合計', '詳細']">
                    @foreach ($attendances as $attendance)
                        <tr>
                            <td class="attendance__data">
                                {{ $attendance->date_display }}<span class="weekday">({{ $attendance->weekday }})</span>
                            </td>
                            <td class="attendance__data">{{ $attendance->clock_in }}</td>
                            <td class="attendance__data">{{ $attendance->clock_out }}</td>
                            <td class="attendance__data">{{ $attendance->break }}</td>
                            <td class="attendance__data">{{ $attendance->total_work }}</td>
                            <td class="attendance__data">
                                @if ($attendance->is_future)
                                    <div class="attendance__detail disabled"><a href="#">詳細</a></div>
                                @else
                                    <a class="attendance__detail" href="{{ route('attendance.detail_or_create', ['date' => $attendance->date->toDateString()]) }}">詳細</a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </x-table>
            </div>
        </div>
    </div>
@endsection
