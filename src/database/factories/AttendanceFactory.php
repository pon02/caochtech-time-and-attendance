<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AttendanceFactory extends Factory
{
    protected $model = Attendance::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'work_date' => $this->faker->date(),
            'clock_in_at' => null,
            'clock_out_at' => null,
            'break_minutes' => 0,
            'work_minutes' => null,
            'note' => null,
            'status' => 'normal',
        ];
    }
}
