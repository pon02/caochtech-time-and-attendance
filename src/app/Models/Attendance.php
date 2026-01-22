<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'work_date',
        'clock_in_at',
        'clock_out_at',
        'break_minutes',
        'work_minutes',
        'note',
        'status',
    ];

    /**
     * 出勤情報とユーザーの多対1リレーション
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 出勤情報と休憩情報の1対多リレーション
     */
    public function breaks()
    {
        return $this->hasMany(AttendanceBreak::class);
    }
}
