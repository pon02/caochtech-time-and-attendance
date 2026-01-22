<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Carbon\Carbon;

class AttendanceDetailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'after_attendance.clock_in_at' => ['required', 'date_format:H:i'],
            'after_attendance.clock_out_at' => ['required', 'date_format:H:i'],
            'after_attendance.note' => ['required', 'string', 'max:100'],
            'after_breaks' => ['array'],
            'after_breaks.*.start_at' => ['nullable', 'date_format:H:i'],
            'after_breaks.*.end_at' => ['nullable', 'date_format:H:i'],
        ];
    }

    public function messages(): array
    {
        return [
            'after_attendance.clock_in_at.required' => '出勤時間もしくは退勤時間が不適切な値です',
            'after_attendance.clock_in_at.date_format' => '出勤時間はHH:MM形式で入力してください',
            'after_attendance.clock_out_at.required' => '出勤時間もしくは退勤時間が不適切な値です',
            'after_attendance.clock_out_at.date_format' => '退勤時間はHH:MM形式で入力してください',
            'after_breaks.*.start_at.date_format' => '休憩開始はHH:MM形式で入力してください',
            'after_breaks.*.end_at.date_format' => '休憩終了はHH:MM形式で入力してください',
            'after_attendance.note.required' => '備考を記入してください',
            'after_attendance.note.string' => '備考を記入してください',
            'after_attendance.note.max' => '備考は100文字以内で入力してください',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $in = $this->input('after_attendance.clock_in_at');
            $out = $this->input('after_attendance.clock_out_at');
            if ($in && $out) {
                try {
                    $inTime = Carbon::createFromFormat('H:i', $in);
                    $outTime = Carbon::createFromFormat('H:i', $out);
                } catch (\Throwable $e) {
                    return;
                }

                if ($inTime->gte($outTime)) {
                    $validator->errors()->add('after_attendance.clock_in_at', '出勤時間もしくは退勤時間が不適切な値です');
                }
                $breaks = $this->input('after_breaks', []);
                foreach ($breaks as $i => $break) {
                    $startRaw = $break['start_at'] ?? null;
                    $endRaw = $break['end_at'] ?? null;

                    $hasStart = !empty($startRaw);
                    $hasEnd = !empty($endRaw);

                    // どちらか片方だけの入力は不可
                    if ($hasStart xor $hasEnd) {
                        $validator->errors()->add('after_breaks.' . $i . '.start_at', '休憩時間が不適切な値です');
                        continue;
                    }

                    $bStart = null;
                    $bEnd = null;
                    if ($hasStart && $hasEnd) {
                        try {
                            $bStart = Carbon::createFromFormat('H:i', $startRaw);
                            $bEnd = Carbon::createFromFormat('H:i', $endRaw);
                        } catch (\Throwable $e) {
                            continue;
                        }

                        // 休憩開始が休憩終了より遅いのは不可
                        if ($bStart->gt($bEnd)) {
                            $validator->errors()->add('after_breaks.' . $i . '.start_at', '休憩時間が不適切な値です');
                            continue;
                        }
                    }

                    if ($bStart !== null) {
                        if ($bStart->lt($inTime) || $bStart->gt($outTime)) {
                            $validator->errors()->add('after_breaks.' . $i . '.start_at', '休憩時間が不適切な値です');
                        }
                    }

                    if ($bEnd !== null) {
                        if ($bEnd->gt($outTime)) {
                            $validator->errors()->add('after_breaks.' . $i . '.end_at', '休憩時間もしくは退勤時間が不適切な値です');
                        }
                    }
                }
            }
        });
    }
}
