@props(['attendanceRequest', 'breaks'])

<table class="attendance__table">
    <x-attendance-edit-row label="名前">
        {{ $attendanceRequest?->user?->name ?? '未申請' }}
    </x-attendance-edit-row>
    <x-attendance-edit-row label="日付">
        <div class="date">
            <p class="year input-date">{{ $attendanceRequest->request_date->format('Y年') }}</p>
            <p class="day input-date">{{ $attendanceRequest->request_date->format('n月j日') }}</p>
        </div>
    </x-attendance-edit-row>
    <x-attendance-edit-row label="出勤・退勤">
        <div class="clock">
            {{ optional($attendanceRequest->clock_in)->format('H:i') }}
            <span>~</span>
            {{ optional($attendanceRequest->clock_out)->format('H:i') }}
        </div>
    </x-attendance-edit-row>
    @foreach($breaks as $index => $break)
        <x-attendance-edit-row :label="$index === 0 ? '休憩' : '休憩' . ($index + 1)">
            <div class="clock">
                {{ $break->break_start_formatted }}<span>~</span>{{ $break->break_end_formatted }}
            </div>
        </x-attendance-edit-row>
    @endforeach
    <x-attendance-edit-row label="備考">
        <p>{{ $attendanceRequest->reason }}</p>
    </x-attendance-edit-row>
</table>