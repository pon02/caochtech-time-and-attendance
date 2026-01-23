<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\User;
use App\Services\AttendanceCalendar;
use App\Services\AttendanceDemoGenerator;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AttendanceSeeder extends Seeder
{
    public function run(): void
    {
        $start = Carbon::create(2025, 11, 1)->startOfDay();
        $end   = Carbon::create(2026,  1, 23)->endOfDay();

        $calendar = new AttendanceCalendar();
        $gen = app(AttendanceDemoGenerator::class);

        $workingDays = $calendar->workingDays($start, $end);
        $users = User::query()->get();

        foreach ($users as $user) {
            foreach ($workingDays as $workDate) {
                [$clockIn, $clockOut] = $gen->makeWorkTimes($workDate);

                Attendance::query()->create([
                    'user_id' => $user->id,
                    'work_date' => $workDate->toDateString(),
                    'clock_in_at' => $clockIn->toDateTimeString(),
                    'clock_out_at' => $clockOut->toDateTimeString(),
                    'break_minutes' => 0,
                    'work_minutes' => null,
                    'note' => null,
                    'status' => 'normal',
                ]);
            }
        }
    }
}
