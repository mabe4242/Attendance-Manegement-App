@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/admin/attendance_index.css') }}">
@endsection

@section('content')
    <x-admin_header />
    <div class="content">
        <div class="attendance__content">
            <div class="attendance__wrapper">
                <p class="attendance__title">{{ $date->isoFormat('YYYY年M月D日') }}の勤怠</p>
                <x-table-menu :prevUrl="$prevUrl" :main="$date->format('Y/m/d')" :nextUrl="$nextUrl" prev="前日" next="翌日"/>
                <x-table :headers=$headers>
                    @foreach ($attendances as $attendance)
                        <tr>
                            <td class="attendance__data">{{ $attendance->userName }}</td>
                            <td class="attendance__data">{{ $attendance->clock_in }}</td>
                            <td class="attendance__data">{{ $attendance->clock_out }}</td>
                            <td class="attendance__data">{{ $attendance->break }}</td>
                            <td class="attendance__data">{{ $attendance->total_work }}</td>
                            <td class="attendance__data">
                                <a class="attendance__detail" href="{{ route('admin.attendance.show', $attendance->id) }}">詳細</a>
                            </td>
                        </tr>
                    @endforeach
                </x-table>
            </div>
        </div>
    </div>
@endsection
