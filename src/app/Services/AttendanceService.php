<?php

namespace App\Services;

use App\Models\Attendance;
use Carbon\Carbon;

class AttendanceService
{
    /* ===== 勤務状態 ===== */

    public function calcStatus(Attendance $attendance): string
    {
        if ($attendance->clock_in_at === null) return '勤務外';
        if ($attendance->clock_out_at !== null) return '退勤済';
        return $this->hasOpenBreak($attendance) ? '休憩中' : '出勤中';
    }

    public function canBreakStart(Attendance $attendance): bool
    {
        return $attendance->clock_in_at !== null
            && $attendance->clock_out_at === null
            && !$this->hasOpenBreak($attendance);
    }

    public function canBreakEnd(Attendance $attendance): bool
    {
        return $attendance->clock_in_at !== null
            && $attendance->clock_out_at === null
            && $this->hasOpenBreak($attendance);
    }

    public function canClockOut(Attendance $attendance): bool
    {
        return $attendance->clock_in_at !== null
            && $attendance->clock_out_at === null
            && !$this->hasOpenBreak($attendance);
    }

    private function hasOpenBreak(Attendance $attendance): bool
    {
        return $attendance->breaks->contains(fn($b) => $b->start_at && $b->end_at === null);
    }

    /* ===== 保存値（一覧/CSV向け） ===== */

    public function storedBreakMinutes(Attendance $attendance): int
    {
        return (int) ($attendance->break_minutes ?? 0);
    }

    public function storedWorkMinutes(Attendance $attendance): ?int
    {
        return $attendance->work_minutes !== null ? (int) $attendance->work_minutes : null;
    }

    /* ===== 再計算して保存 ===== */

    public function recalculateAndSave(Attendance $attendance): void
    {
        if (!$attendance->relationLoaded('breaks')) {
            $attendance->load('breaks');
        }

        $breakMinutes = $attendance->breaks
            ->filter(fn($b) => $b->start_at && $b->end_at)
            ->sum(fn($b) => Carbon::parse($b->end_at)->diffInMinutes(Carbon::parse($b->start_at)));

        $workMinutes = null;
        if ($attendance->clock_in_at && $attendance->clock_out_at) {
            $total = Carbon::parse($attendance->clock_out_at)
                ->diffInMinutes(Carbon::parse($attendance->clock_in_at));
            $workMinutes = max(0, $total - $breakMinutes);
        }

        $attendance->update([
            'break_minutes' => $breakMinutes,
            'work_minutes' => $workMinutes,
        ]);
    }

    /* ===== 画面用整形（詳細画面） ===== */

    public function makeBreakFormRows(Attendance $attendance): array
    {
        $rows = $attendance->breaks
            ->sortBy('start_at')
            ->map(fn($b) => [
                'id' => $b->id,
                'start_at' => $b->start_at,
                'end_at' => $b->end_at,
            ])
            ->values()
            ->all();

        $rows[] = ['id' => null, 'start_at' => null, 'end_at' => null];
        return $rows;
    }
}
