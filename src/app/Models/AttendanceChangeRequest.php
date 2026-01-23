<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceChangeRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_id',
        'requester_user_id',
        'status',
        'reviewer_user_id',
        'reviewed_at',
    ];

    protected $casts = [
        'attendance_id' => 'integer',
    ];

    /**
     * 勤怠変更申請と勤怠情報の多対1リレーション
     */
    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    /**
     * 勤怠変更申請と申請者ユーザーの多対1リレーション
     */
    public function requesterUser()
    {
        return $this->belongsTo(User::class, 'requester_user_id');
    }

    /**
     * 勤怠変更申請と承認者ユーザーの多対1リレーション
     */
    public function reviewerUser()
    {
        return $this->belongsTo(User::class, 'reviewer_user_id');
    }

    /**
     * 勤怠変更申請とペイロードの1対1リレーション
     */
    public function payload()
    {
        return $this->hasOne(AttendanceChangeRequestPayload::class);
    }
}
