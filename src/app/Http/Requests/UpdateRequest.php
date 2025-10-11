<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use App\Services\AttendanceTimeValidator as TimeValidator;

class UpdateRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'clock_in' => $this->normalizeTime($this->input('clock_in')),
            'clock_out' => $this->normalizeTime($this->input('clock_out')),
            'breaks' => collect($this->input('breaks', []))->map(function ($break) {
                return [
                    'id' => $break['id'] ?? null,
                    'break_start' => $this->normalizeTime($break['break_start'] ?? null),
                    'break_end' => $this->normalizeTime($break['break_end'] ?? null),
                ];
            })->toArray(),
        ]);
    }

    private function normalizeTime($time)
    {
        if (!$time) return $time;
        if (preg_match('/^(\d{1,2}):(\d{2})$/', $time, $matches)) {
            return str_pad($matches[1], 2, '0', STR_PAD_LEFT) . ':' . $matches[2];
        }
        return $time;
    }

    public function rules()
    {
        return [
            'clock_in' => ['nullable', 'regex:/^(?:[0-1]?\d|2[0-4]):[0-5]\d$/'],
            'clock_out' => ['nullable', 'regex:/^(?:[0-1]?\d|2[0-4]):[0-5]\d$/'],
            'reason' => ['required', 'string'],
            'breaks' => ['array'],
            'breaks.*.id' => ['nullable', 'integer'],
            'breaks.*.break_start' => ['nullable', 'regex:/^(?:[0-1]?\d|2[0-4]):[0-5]\d$/'],
            'breaks.*.break_end' => ['nullable', 'regex:/^(?:[0-1]?\d|2[0-4]):[0-5]\d$/'],
        ];
    }

    public function messages()
    {
        return [
            'clock_in.regex' => '出勤時刻は「HH:MM」形式で時刻を数値で入力してください。',
            'clock_out.regex' => '退勤時刻は「HH:MM」形式で時刻を数値で入力してください。',
            'breaks.*.break_start.regex' => '休憩開始は「HH:MM」形式で時刻を数値で入力してください。',
            'breaks.*.break_end.regex' => '休憩終了は「HH:MM」形式で時刻を数値で入力してください。',
            'reason.required' => '備考を記入してください。',
        ];
    }

    public function withValidator(Validator $validator)
    {
        $validator->after(function ($validator) {
            $clockIn = $this->input('clock_in');
            $clockOut = $this->input('clock_out');
            $breaks = $this->input('breaks', []);

            if ($clockIn && !$clockOut) $validator->errors()->add('clock_out', '出勤時間もしくは退勤時間が不適切な値です');
            if ($clockOut && !$clockIn) $validator->errors()->add('clock_in', '出勤時間もしくは退勤時間が不適切な値です');

            if (TimeValidator::isValidTime($clockIn) && TimeValidator::isValidTime($clockOut)) {
                if (!TimeValidator::isBefore($clockIn, $clockOut)) {
                    $validator->errors()->add('clock_in', '出勤時間もしくは退勤時間が不適切な値です');
                    $validator->errors()->add('clock_out', '出勤時間もしくは退勤時間が不適切な値です');
                }
            }

            foreach ($breaks as $index => $break) {
                $start = $break['break_start'] ?? null;
                $end   = $break['break_end'] ?? null;

                if ((!$clockIn || !TimeValidator::isValidTime($clockIn)) && (!$clockOut || !TimeValidator::isValidTime($clockOut))) {
                    if ($start || $end) {
                        $validator->errors()->add("breaks.$index.break_start", '休憩時間もしくは退勤時間が不適切な値です');
                        $validator->errors()->add("breaks.$index.break_end", '休憩時間もしくは退勤時間が不適切な値です');
                        continue;
                    }
                }

                if ($start && !TimeValidator::isValidTime($start) && $start !== null) {
                    $validator->errors()->add("breaks.$index.break_start", '休憩開始は「HH:MM」形式で数値で入力してください。');
                    continue;
                }
                if ($end && !TimeValidator::isValidTime($end) && $end !== null) {
                    $validator->errors()->add("breaks.$index.break_end", '休憩終了は「HH:MM」形式で数値で入力してください。');
                    continue;
                }

                if ($start && !$end) $validator->errors()->add("breaks.$index.break_end", '休憩時間が不適切な値です');
                if ($end && !$start) $validator->errors()->add("breaks.$index.break_start", '休憩時間が不適切な値です');

                if ($start && TimeValidator::isValidTime($clockIn) && !TimeValidator::isAfterOrEqual($start, $clockIn)) {
                    $validator->errors()->add("breaks.$index.break_start", '休憩時間が不適切な値です');
                }
                if ($start && TimeValidator::isValidTime($clockOut) && !TimeValidator::isBeforeOrEqual($start, $clockOut)) {
                    $validator->errors()->add("breaks.$index.break_start", '休憩時間が不適切な値です');
                }
                if ($end && TimeValidator::isValidTime($clockOut) && !TimeValidator::isBeforeOrEqual($end, $clockOut)) {
                    $validator->errors()->add("breaks.$index.break_end", '休憩時間もしくは退勤時間が不適切な値です');
                }
                if ($start && $end && !TimeValidator::isBefore($start, $end)) {
                    $validator->errors()->add("breaks.$index.break_end", '休憩時間もしくは退勤時間が不適切な値です');
                }
            }

            // 休憩重複チェック
            TimeValidator::checkBreakOverlap($validator, $breaks);
        });
    }
}
