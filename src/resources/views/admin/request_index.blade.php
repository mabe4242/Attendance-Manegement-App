@php use App\Enums\RequestStatus; @endphp

@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/admin/attendance_index.css') }}">
@endsection

@section('content')
    <x-admin_header />
    <div class="content">
        <div class="attendance__content">
            <div class="attendance__wrapper">
                <p class="attendance__title">申請一覧</p>
                <x-nav :status="$status" routeName="admin.attendance_requests.index" />
                <x-table :headers=$headers>
                    @foreach ($attendanceRequests as $request)
                        <tr>
                            <td class="attendance__data">{{ RequestStatus::label($request->status) }}</td>
                            <td class="attendance__data">{{ $request->user->name }}</td>
                            <td class="attendance__data">{{ $request->request_date->format('Y/m/d') }}</td>
                            <td class="attendance__data">{{ $request->reason }}</td>
                            <td class="attendance__data">{{ $request->created_at->format('Y/m/d') }}</td>
                            <td class="attendance__data">
                                <a class="attendance__detail" href="{{ route('admin.request', $request->id) }}">詳細</a>
                            </td>
                        </tr>
                    @endforeach
                </x-table>
            </div>
        </div>
    </div>
@endsection