<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceChangeRequestPayload extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_change_request_id',
        'before_attendance',
        'after_attendance',
        'before_breaks',
        'after_breaks',
    ];

    protected $casts = [
        'before_attendance' => 'array',
        'after_attendance' => 'array',
        'before_breaks' => 'array',
        'after_breaks' => 'array',
    ];

    /**
     * 勤怠変更申請ペイロードと勤怠変更申請の1対1リレーション（逆側）
     */
    public function attendanceChangeRequest()
    {
        return $this->belongsTo(AttendanceChangeRequest::class);
    }
}
