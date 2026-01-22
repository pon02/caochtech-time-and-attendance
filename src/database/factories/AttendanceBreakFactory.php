<?php

namespace Database\Factories;

use App\Models\AttendanceBreak;
use App\Models\Attendance;
use Illuminate\Database\Eloquent\Factories\Factory;

class AttendanceBreakFactory extends Factory
{
    protected $model = AttendanceBreak::class;

    public function definition(): array
    {
        return [
            'attendance_id' => Attendance::factory(),
            'start_at' => null,
            'end_at' => null,
        ];
    }
}
