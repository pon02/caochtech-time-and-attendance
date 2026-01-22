<?php

namespace Database\Factories;

use App\Models\AttendanceAudit;
use App\Models\Attendance;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AttendanceAuditFactory extends Factory
{
    protected $model = AttendanceAudit::class;

    public function definition(): array
    {
        return [
            'attendance_id' => Attendance::factory(),
            'actor_user_id' => User::factory(),
            'action' => 'apply_change_request',
            'before_change' => [],
            'after_change' => [],
        ];
    }
}
