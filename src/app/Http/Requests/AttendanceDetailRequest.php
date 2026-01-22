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
                $inTime = Carbon::parse($in);
                $outTime = Carbon::parse($out);
                if ($inTime->gte($outTime)) {
                    $validator->errors()->add('after_attendance.clock_in_at', '出勤時間もしくは退勤時間が不適切な値です');
                }
                $breaks = $this->input('after_breaks', []);
                foreach ($breaks as $i => $break) {
                    if (!empty($break['start_at'])) {
                        $bStart = Carbon::parse($break['start_at']);
                        if ($bStart->lt($inTime) || $bStart->gt($outTime)) {
                            $validator->errors()->add('after_breaks.'.$i.'.start_at', '休憩時間が不適切な値です');
                        }
                    }
                    if (!empty($break['end_at'])) {
                        $bEnd = Carbon::parse($break['end_at']);
                        if ($bEnd->gt($outTime)) {
                            $validator->errors()->add('after_breaks.'.$i.'.end_at', '休憩時間もしくは退勤時間が不適切な値です');
                        }
                    }
                }
            }
        });
    }
}
