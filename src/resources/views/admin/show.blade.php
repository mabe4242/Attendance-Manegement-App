@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/admin/attendance_request.css') }}">
@endsection

@section('content')
    <x-admin_header />
    <div class="content">
        <div class="attendance__content">
            <div class="attendance__wrapper">
                <p class="attendance__title">勤怠詳細</p>
                <x-attendance-request-form :attendance="$attendance" :breaks="$breaks" :errors="$errors" 
                    :formAction="route('admin.attendance.update', $attendance->id)" method="PUT"/>
            </div>
        </div>
    </div>
@endsection