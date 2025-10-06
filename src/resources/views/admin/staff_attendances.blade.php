@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/admin/attendance_index.css') }}">
@endsection

@section('content')
    <x-admin_header />
    <div class="content">
        <div class="attendance__content">
            <div class="attendance__wrapper">
                <p class="attendance__title">{{ $user->user_name }}さんの勤怠</p>
                <x-table-menu :prevUrl="$prevMonthUrl" :main="$month" :nextUrl="$nextMonthUrl" prev="前月" next="翌月"/>
                <x-table :headers=$headers>
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
                                    <a class="attendance__detail" href="{{ route('admin.detail_or_create', 
                                        ['user' => $user->id, 'date' => $attendance->date->toDateString()]) }}">詳細</a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </x-table>
                <div class="export-button">
                    <a href="{{ route('admin.csv', ['id' => $user->id]) }}?month={{ $month }}" class="export-button__text">CSV出力</a>
                </div>
            </div>
        </div>
    </div>
@endsection
