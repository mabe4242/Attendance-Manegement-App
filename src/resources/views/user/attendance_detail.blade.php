@php use App\Enums\RequestStatus; @endphp

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
                @if(isset($attendanceRequest) && $attendanceRequest->status == RequestStatus::PENDING)
                    <x-attendance-approve-view :attendanceRequest="$attendanceRequest" :breaks="$breaks" />
                    <p class="approve__message">*承認待ちのため修正はできません。</p>
                @elseif(isset($attendanceRequest) && $attendanceRequest->status == RequestStatus::APPROVED && $source === 'request_list')
                    <x-attendance-approve-view :attendanceRequest="$attendanceRequest" :breaks="$breaks" />
                    <p class="approve__message">*承認済み（管理者名: {{$attendanceRequest->admin->name}}）</p>
                @else
                    <x-attendance-request-form :attendance="$attendance" :breaks="$breaks" :errors="$errors" 
                        :formAction="route('attendance_request.store',  $attendance->id)"/>
                @endif
            </div>
        </div>
    </div>
@endsection