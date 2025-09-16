@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/user/attendance_index.css') }}">
@endsection

@section('content')
    <x-user_header></x-user_header>
    <div class="content">
        <div class="attendance__content">
            <div class="attendance__wrapper">
                <p class="attendance__title">勤怠一覧</p>
                <x-table-menu :prevMonthUrl="$prevMonthUrl" :month="$month" :nextMonthUrl="$nextMonthUrl" />
                <x-table :headers="['日付', '出勤', '退勤', '休憩', '合計', '詳細']">
                    @foreach ($attendances as $attendance)
                        <tr>
                            <td class="attendance__deta">
                                {{ $attendance->date_display }}<span class="weekday">({{ $attendance->weekday }})</span>
                            </td>
                            <td class="attendance__deta">{{ $attendance->clock_in }}</td>
                            <td class="attendance__deta">{{ $attendance->clock_out }}</td>
                            <td class="attendance__deta">{{ $attendance->break }}</td>
                            <td class="attendance__deta">{{ $attendance->total_work }}</td>
                            <td class="attendance__deta">
                                {{-- <a class="attendance__detail" href="{{ route('attendance.detail', ['id' => $attendance->id]) }}">詳細</a> --}}
                                <a class="attendance__detail" href="">詳細</a>
                            </td>
                        </tr>
                    @endforeach
                </x-table>
            </div>
        </div>
    </div>
@endsection
