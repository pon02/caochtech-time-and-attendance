<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'email_verified_at',
        'password',
        'role_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * ユーザーと役割のリレーションシップ
     **/
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * ユーザーと出勤情報の1対多リレーション
     */
    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * ユーザーと出勤変更申請の1対多リレーション（申請者）
     */
    public function attendanceChangeRequestsAsRequester()
    {
        return $this->hasMany(AttendanceChangeRequest::class, 'requester_user_id');
    }

    /**
     * ユーザーと出勤変更申請の1対多リレーション（承認者）
     */
    public function attendanceChangeRequestsAsReviewer()
    {
        return $this->hasMany(AttendanceChangeRequest::class, 'reviewer_user_id');
    }
}
