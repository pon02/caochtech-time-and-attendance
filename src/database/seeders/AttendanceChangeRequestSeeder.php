<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\User;
use App\Services\AttendanceDemoGenerator;
use Illuminate\Database\Seeder;

class AttendanceChangeRequestSeeder extends Seeder
{
    public function run(): void
    {
        $gen = app(AttendanceDemoGenerator::class);

        $allAttendances = Attendance::query()->get();
        $targetCount = (int) floor($allAttendances->count() * 0.10);

        $targets = $allAttendances->shuffle()->take($targetCount)->values();

        $approvedCount = (int) floor($targets->count() * 0.50);
        $approved = $targets->take($approvedCount);
        $pending  = $targets->slice($approvedCount);

        $reviewers = User::query()->where('role_id', 1)->get();

        foreach ($approved as $attendance) {
            $gen->createChangeRequestWithPayload(
                attendance: $attendance,
                status: 'approved',
                reviewer: $reviewers->random(),
                apply: true
            );
        }

        foreach ($pending as $attendance) {
            // 申請内容を生成
            $before = [
                'clock_in_at' => (string) $attendance->clock_in_at,
                'clock_out_at' => (string) $attendance->clock_out_at,
                'note' => $attendance->note,
            ];
            // createChangeRequestWithPayloadで生成されるafter_attendanceを再現
            $note = $gen->maybeNote(1.0);
            $clockIn = $attendance->work_date . ' 09:00:00';
            $clockOut = $attendance->work_date . ' 18:00:00';
            $workMinutes = 8 * 60;
            if ($note && $note !== '電車遅延') {
                $shorten = random_int(30, 60);
                if (random_int(0, 1) === 0) {
                    $clockIn = \Carbon\Carbon::parse($clockIn)->addMinutes($shorten)->toDateTimeString();
                } else {
                    $clockOut = \Carbon\Carbon::parse($clockOut)->subMinutes($shorten)->toDateTimeString();
                }
                $workMinutes = \Carbon\Carbon::parse($clockIn)->diffInMinutes(\Carbon\Carbon::parse($clockOut));
            }
            $attendance->update([
                'note' => $note,
                'work_minutes' => $workMinutes,
                'clock_in_at' => $clockIn,
                'clock_out_at' => $clockOut,
            ]);
            $gen->createChangeRequestWithPayload(
                attendance: $attendance,
                status: 'pending',
                reviewer: null,
                apply: false
            );
        }
    }
}
