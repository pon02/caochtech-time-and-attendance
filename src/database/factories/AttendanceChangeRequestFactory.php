<?php

namespace Database\Factories;

use App\Models\AttendanceChangeRequest;
use App\Models\Attendance;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AttendanceChangeRequestFactory extends Factory
{
    protected $model = AttendanceChangeRequest::class;

    public function definition(): array
    {
        return [
            'attendance_id' => Attendance::factory(),
            'requester_user_id' => User::factory(),
            'status' => 'pending',
            'reviewer_user_id' => null,
            'reviewed_at' => null,
        ];
    }
}
