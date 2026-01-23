<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceAudit extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_id',
        'actor_user_id',
        'action',
        'before_change',
        'after_change',
    ];

    protected $casts = [
        'before_change' => 'array',
        'after_change' => 'array',
    ];

    /**
     * 勤怠監査ログと勤怠の多対1リレーション
     */
    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    /**
     * 勤怠監査ログと操作ユーザーの多対1リレーション
     */
    public function actorUser()
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
