<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Carbon\Carbon;

class UpdateRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            // ② 数値・形式チェックと③ 定義域チェックを正規表現で実装
            'clock_in' => [
                'nullable',
                'regex:/^(?:[01]\d|2[0-4]):[0-5]\d$/',
            ],
            'clock_out' => [
                'nullable',
                'regex:/^(?:[01]\d|2[0-4]):[0-5]\d$/',
            ],

            'reason' => ['required', 'string'],

            'breaks' => ['array'],
            'breaks.*.id' => ['nullable', 'integer'],
            'breaks.*.break_start' => ['nullable', 'regex:/^(?:[01]\d|2[0-4]):[0-5]\d$/'],
            'breaks.*.break_end' => ['nullable', 'regex:/^(?:[01]\d|2[0-4]):[0-5]\d$/'],
        ];
    }

    public function messages()
    {
        return [
            // ② HH:MM形式の数値チェック
            'clock_in.regex' => '出勤時刻は「HH:MM」形式で数値で入力してください。',
            'clock_out.regex' => '退勤時刻は「HH:MM」形式で数値で入力してください。',
            'breaks.*.break_start.regex' => '休憩開始は「HH:MM」形式で数値で入力してください。',
            'breaks.*.break_end.regex' => '休憩終了は「HH:MM」形式で数値で入力してください。',

            'reason.required' => '備考を記入してください。',
        ];
    }

    public function withValidator(Validator $validator)
    {
        $validator->after(function ($validator) {
            $clockIn = $this->input('clock_in');
            $clockOut = $this->input('clock_out');
            $breaks = $this->input('breaks', []);

            // ---- ① 出退勤入力必須条件 ----
            if ($clockIn && !$clockOut) {
                $validator->errors()->add('clock_out', '出勤時間もしくは退勤時間が不適切な値です');
            }
            if ($clockOut && !$clockIn) {
                $validator->errors()->add('clock_in', '出勤時間もしくは退勤時間が不適切な値です');
            }

            // ---- 出退勤の前後関係 ----
            if ($clockIn && $clockOut) {
                if (!$this->isBefore($clockIn, $clockOut)) {
                    $validator->errors()->add('clock_in', '出勤時間もしくは退勤時間が不適切な値です');
                    $validator->errors()->add('clock_out', '出勤時間もしくは退勤時間が不適切な値です');
                }
            }

            // ---- 休憩のバリデーション ----
            foreach ($breaks as $index => $break) {
                $start = $break['break_start'] ?? null;
                $end   = $break['break_end'] ?? null;

                // ① 入力必須条件（片方のみ入力）
                if ($start && !$end) {
                    $validator->errors()->add("breaks.$index.break_end", '休憩時間が不適切な値です');
                }
                if ($end && !$start) {
                    $validator->errors()->add("breaks.$index.break_start", '休憩時間が不適切な値です');
                }

                // 出勤・退勤がある場合の範囲チェック
                if ($start && $clockIn && !$this->isAfterOrEqual($start, $clockIn)) {
                    $validator->errors()->add("breaks.$index.break_start", '休憩時間が不適切な値です');
                }

                if ($start && $clockOut && !$this->isBeforeOrEqual($start, $clockOut)) {
                    $validator->errors()->add("breaks.$index.break_start", '休憩時間が不適切な値です');
                }

                if ($end && $clockOut && !$this->isBeforeOrEqual($end, $clockOut)) {
                    $validator->errors()->add("breaks.$index.break_end", '休憩時間もしくは退勤時間が不適切な値です');
                }

                if ($start && $end && !$this->isBefore($start, $end)) {
                    $validator->errors()->add("breaks.$index.break_end", '休憩時間もしくは退勤時間が不適切な値です');
                }
            }

            // ---- 休憩時間の重複チェック ----
            $this->checkBreakOverlap($validator, $breaks);
        });
    }

    /**
     * 時刻比較ヘルパー
     */
    private function isBefore(string $time1, string $time2)
    {
        return Carbon::parse($time1)->lt(Carbon::parse($time2));
    }

    private function isBeforeOrEqual(string $time1, string $time2)
    {
        return Carbon::parse($time1)->lte(Carbon::parse($time2));
    }

    private function isAfterOrEqual(string $time1, string $time2)
    {
        return Carbon::parse($time1)->gte(Carbon::parse($time2));
    }

    /**
     * 休憩時間の重複を検出
     */
    private function checkBreakOverlap(Validator $validator, array $breaks)
    {
        $times = [];

        foreach ($breaks as $index => $break) {
            $start = $break['break_start'] ?? null;
            $end   = $break['break_end'] ?? null;

            if (!$start || !$end) {
                continue;
            }

            $startTime = Carbon::parse($start);
            $endTime   = Carbon::parse($end);

            foreach ($times as $i => [$prevStart, $prevEnd]) {
                if ($startTime->lt($prevEnd) && $endTime->gt($prevStart)) {
                    $validator->errors()->add("breaks.$index.break_start", '休憩時間が他の休憩と重複しています');
                    $validator->errors()->add("breaks.$index.break_end", '休憩時間が他の休憩と重複しています');
                }
            }

            $times[] = [$startTime, $endTime];
        }
    }
}
