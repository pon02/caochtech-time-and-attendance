<?php

namespace Database\Factories;

use App\Models\AttendanceChangeRequestPayload;
use App\Models\AttendanceChangeRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

class AttendanceChangeRequestPayloadFactory extends Factory
{
    protected $model = AttendanceChangeRequestPayload::class;

    public function definition(): array
    {
        return [
            'attendance_change_request_id' => AttendanceChangeRequest::factory(),
            'before_attendance' => [],
            'after_attendance' => [],
            'before_breaks' => [],
            'after_breaks' => [],
        ];
    }
}
