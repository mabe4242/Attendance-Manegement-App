@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/user/attendance_request.css') }}">
@endsection

@section('content')
    <x-user_header />
    <div class="content">
        <div class="attendance__content">
            <div class="attendance__wrapper">
                <p class="attendance__title">勤怠詳細</p>
                @php use App\Enums\RequestStatus; @endphp
                @if(isset($attendanceRequest) && $attendanceRequest->status == RequestStatus::PENDING)
                    <x-attendance-approve-view :attendanceRequest="$attendanceRequest" :breaks="$breaks" />
                @else
                    <x-attendance-request-form :attendance="$attendance" :breaks="$breaks" :errors="$errors" />
                @endif
            </div>
        </div>
    </div>
@endsection