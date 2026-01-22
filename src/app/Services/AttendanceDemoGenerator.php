<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\AttendanceBreak;
use App\Models\AttendanceChangeRequest;
use App\Models\AttendanceChangeRequestPayload;
use App\Models\AttendanceAudit;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AttendanceDemoGenerator {

    private function splitBreakMinutes(int $count): array
    {
        if ($count === 2) {
            $first = random_int(15, 45);
            return [$first, 60 - $first];
        }
        $first = random_int(15, 30);
        $second = random_int(15, 30);
        $third = max(15, min(30, 60 - $first - $second));
        return [$first, $second, $third];
    }

    public function __construct(private AttendanceService $attendanceService) {}

    public function makeWorkTimes(Carbon $workDate): array
    {
        $in = $workDate->copy()->setTime(9, 0);
        // 10件に1件だけ残業（18:30または19:00退勤）
        $rand = random_int(1, 10);
        if ($rand === 1) {
            $out = $workDate->copy()->setTime(random_int(0, 1) ? 18 : 19, 0);
            if ($out->hour === 18) {
                $out->addMinutes(30); // 18:30
            }
        } else {
            $out = $workDate->copy()->setTime(18, 0);
        }
        return [$in, $out];
    }

    /**
     * 休憩生成
     */
    public function makeBreaks(Attendance $attendance): array
    {
        $breaks = [];
        if (random_int(1, 100) <= 90) {
            $breaks[] = [
                'start_at' => $attendance->work_date.' 12:00:00',
                'end_at'   => $attendance->work_date.' 13:00:00',
            ];
        } else {
            $breakCount = random_int(2, 3);
            $minutes = $this->splitBreakMinutes($breakCount);
            $slots = [
                [$attendance->work_date.' 10:00:00', $attendance->work_date.' 11:00:00'],
                [$attendance->work_date.' 12:00:00', $attendance->work_date.' 13:30:00'],
                [$attendance->work_date.' 15:00:00', $attendance->work_date.' 17:00:00'],
            ];
            shuffle($slots);
            for ($i = 0; $i < $breakCount; $i++) {
                [$slotStartStr, $slotEndStr] = $slots[$i % count($slots)];
                $slotStart = Carbon::parse($slotStartStr);
                $room = max(0, Carbon::parse($slotEndStr)->diffInMinutes($slotStart) - $minutes[$i]);
                $start = $slotStart->copy()->addMinutes(random_int(0, $room));
                $breaks[] = [
                    'start_at' => $start->toDateTimeString(),
                    'end_at'   => $start->copy()->addMinutes($minutes[$i])->toDateTimeString(),
                ];
            }
            $total = array_sum($minutes);
            if ($total !== 60 && count($breaks)) {
                $breaks[count($breaks)-1]['end_at'] = Carbon::parse($breaks[count($breaks)-1]['end_at'])->addMinutes(60-$total)->toDateTimeString();
            }
        }
        usort($breaks, fn($a, $b) => strcmp($a['start_at'], $b['start_at']));
        return $breaks;
    }

    public function maybeNote(float $probability = 0.25): ?string
    {
        $probability = max(0.0, min(1.0, $probability));

        if (random_int(1, 100) > (int) round($probability * 100)) {
            return null;
        }

        $notes = [
            '電車遅延',
            '通院',
            '体調不良',
            '家庭都合',
        ];

        return $notes[array_rand($notes)];
    }

    /**
     * 修正申請＋payload作成。approvedの場合は反映＆audit記録。
     */
    public function createChangeRequestWithPayload(
        Attendance $attendance,
        string $status,
        ?User $reviewer,
        bool $apply
    ): void {
        $attendance->load(['breaks' => fn($q) => $q->orderBy('start_at')]);

        $beforeAttendance = [
            'clock_in_at' => (string) $attendance->clock_in_at,
            'clock_out_at' => (string) $attendance->clock_out_at,
            'note' => $attendance->note,
        ];

        $beforeBreaks = $attendance->breaks->map(fn($b) => [
            'id' => $b->id,
            'start_at' => $b->start_at,
            'end_at' => $b->end_at,
        ])->values()->all();

        // 修正申請用noteを生成
        $note = $this->maybeNote(1.0);
        $clockIn = Carbon::parse($attendance->work_date.' 09:00:00');
        $clockOut = Carbon::parse($attendance->work_date.' 18:00:00');
        $workMinutes = 8 * 60;

        if ($note && $note !== '電車遅延') {
            // 出勤遅刻 or 早退をランダムで決定（30〜60分）
            $shorten = random_int(30, 60);
            if (random_int(0, 1) === 0) {
                $clockIn->addMinutes($shorten);
            } else {
                $clockOut->subMinutes($shorten);
            }
            $workMinutes = $clockIn->diffInMinutes($clockOut);
        }

        $afterAttendance = [
            'clock_in_at' => $clockIn->toDateTimeString(),
            'clock_out_at' => $clockOut->toDateTimeString(),
            'note' => $note,
            'work_minutes' => $workMinutes,
        ];

        $afterBreaks = [[
            'id' => null,
            'start_at' => Carbon::parse($attendance->work_date.' 12:00:00')->toDateTimeString(),
            'end_at'   => Carbon::parse($attendance->work_date.' 13:00:00')->toDateTimeString(),
        ]];

        $req = AttendanceChangeRequest::query()->create([
            'attendance_id' => $attendance->id,
            'requester_user_id' => $attendance->user_id,
            'status' => $status,
            'reviewer_user_id' => $reviewer?->id,
            'reviewed_at' => $reviewer ? now() : null,
        ]);

        AttendanceChangeRequestPayload::query()->create([
            'attendance_change_request_id' => $req->id,
            'before_attendance' => $beforeAttendance,
            'after_attendance' => $afterAttendance,
            'before_breaks' => $beforeBreaks,
            'after_breaks' => $afterBreaks,
        ]);

        // always: breaksを全削除してafterBreaksのみ新規作成
        AttendanceBreak::query()->where('attendance_id', $attendance->id)->delete();
        foreach ($afterBreaks as $b) {
            AttendanceBreak::query()->create([
                'attendance_id' => $attendance->id,
                'start_at' => $b['start_at'],
                'end_at' => $b['end_at'],
            ]);
        }
        $attendance->update([
            'clock_in_at' => $afterAttendance['clock_in_at'],
            'clock_out_at' => $afterAttendance['clock_out_at'],
            'note' => $afterAttendance['note'],
            'status' => $apply ? 'approved' : 'pending',
        ]);
        $attendance->refresh()->load('breaks');
        $this->attendanceService->recalculateAndSave($attendance);
        AttendanceAudit::query()->create([
            'attendance_id' => $attendance->id,
            'actor_user_id' => $reviewer?->id ?? $attendance->user_id,
            'action' => 'apply_change_request',
            'before_change' => $beforeAttendance,
            'after_change' => ['attendance' => $afterAttendance, 'breaks' => $afterBreaks,],
        ]);
    }
}
