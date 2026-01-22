<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\AttendanceBreak;
use App\Services\AttendanceDemoGenerator;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AttendanceBreakSeeder extends Seeder
{
    public function run(): void
    {
        $gen = app(AttendanceDemoGenerator::class);
        $attendanceService = app(AttendanceService::class);

        Attendance::query()
            ->orderBy('id')
            ->chunkById(500, function ($attendances) use ($gen, $attendanceService) {
                foreach ($attendances as $attendance) {
                    AttendanceBreak::query()->where('attendance_id', $attendance->id)->delete();
                    $clockIn  = Carbon::parse($attendance->clock_in_at);
                    $clockOut = Carbon::parse($attendance->clock_out_at);

                    $breaks = $gen->makeBreaks($attendance);

                    foreach ($breaks as $b) {
                        AttendanceBreak::query()->create([
                            'attendance_id' => $attendance->id,
                            'start_at' => $b['start_at'],
                            'end_at' => $b['end_at'],
                        ]);
                    }
                    $attendance->refresh()->load('breaks');
                    $attendanceService->recalculateAndSave($attendance);
                }
            });
    }
}
