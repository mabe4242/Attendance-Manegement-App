@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/admin/attendance_index.css') }}">
@endsection

@section('content')
    <x-admin_header />
    <div class="content">
        <div class="attendance__content">
            <div class="attendance__wrapper">
                <p class="attendance__title">スタッフ一覧</p>
                <div class="attendance__staff-wrapper">
                    <x-table :headers="['名前', 'メールアドレス', '月次勤怠']">
                        @foreach ($users as $user)
                            <tr>
                                <td class="attendance__data">{{ $user->user_name }}</td>
                                <td class="attendance__data">{{ $user->email }}</td>
                                <td class="attendance__data">
                                    <a class="attendance__detail" href="{{route('admin.staff_attendance', ['id' => $user->id])}}">詳細</a>
                                </td>
                            </tr>
                        @endforeach
                    </x-table>
                </div>
            </div>
        </div>
    </div>
@endsection
