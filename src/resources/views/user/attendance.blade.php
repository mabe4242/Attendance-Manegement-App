@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/user/attendance.css') }}">
@endsection

@section('content')
    <x-user_header></x-user_header>
    <div class="content__wrapper">
        <div class="content">
            @php use App\Enums\AttendanceStatus; @endphp
            <x-attendance-clock :status="AttendanceStatus::label($attendance->status)"/>
            @if($attendance->status === AttendanceStatus::OFF)
                <x-button route="attendance.store" text="出勤" />
            @elseif($attendance->status === AttendanceStatus::WORKING)
                <div class="btn__area">
                    <x-button route="attendance.checkout" text="退勤" />
                    <x-button route="break.start" :param="$attendance->id" text="休憩入" class="break_btn"/>
                </div>
            @elseif($attendance->status === AttendanceStatus::BREAK)
                <x-button route="break.end" :param="$attendance->id" text="休憩戻" class="break_btn"/>
            @elseif($attendance->status === AttendanceStatus::FINISHED)
                <p class="message">お疲れ様でした。</p>
            @endif
        </div>
    </div>
@endsection