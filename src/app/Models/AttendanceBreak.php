<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceBreak extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_id',
        'start_at',
        'end_at',
    ];

    /**
     * 休憩情報と出勤情報の多対1リレーション
     */
    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }
}
