@props([
    'attendance', 
    'breaks', 
    'errors', 
    'formAction', 
    'method' => 'POST'
])

<form action="{{ $formAction }}" class="attendance_detail" method="POST">
    @csrf
    @if(strtoupper($method) !== 'POST')
        @method($method)
    @endif
    <table class="attendance__table">
        <x-attendance-edit-row label="名前">{{ $attendance->user->name }}</x-attendance-edit-row>
        <x-attendance-edit-row label="日付">
            <div class="date">
                <input type="text" name="year" class="year input-date" value="{{ old('year', $attendance->year) }}">
                <input type="text" name="month_day" class="day input-date" value="{{ old('month_day', $attendance->month_day) }}">
            </div>
            @error('month_day')
                <p class="error__message">{{ $message }}</p>
            @enderror
        </x-attendance-edit-row>
        <x-attendance-edit-row label="出勤・退勤">
            <div class="clock">
                <input type="text" name="clock_in" class="clock-input" value="{{ old('clock_in', $attendance->clock_in_formatted) }}">
                <span>~</span>
                <input type="text" name="clock_out" class="clock-input" value="{{ old('clock_out', $attendance->clock_out_formatted) }}">
            </div>
            @error('clock_in')
                <p class="error__message">{{ $message }}</p>
            @enderror
            @error('clock_out')
                <p class="error__message">{{ $message }}</p>
            @enderror
        </x-attendance-edit-row>
        @foreach($breaks as $index => $break)
            <x-attendance-edit-break-row :index="$index" :break="$break" :errors="$errors" />
        @endforeach
        <x-attendance-edit-break-row :index="count($breaks)" :break="[]" :errors="$errors" />
        <x-attendance-edit-row label="備考">
            <textarea name="reason">{{ old('reason', $attendance->reason ?? '') }}</textarea>
            @error('reason')
                <p class="error__message">{{ $message }}</p>
            @enderror
        </x-attendance-edit-row>
    </table>
    <button type="submit" class="request__button">修正</button>
</form>