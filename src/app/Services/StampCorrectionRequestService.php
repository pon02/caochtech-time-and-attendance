<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\AttendanceBreak;
use App\Models\AttendanceChangeRequest;
use App\Models\AttendanceChangeRequestPayload;
use App\Models\AttendanceAudit;
use App\Services\AttendanceService;
use App\Models\User;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class StampCorrectionRequestService
{
    private function normalizeDateTime(?string $value, string $workDate): ?string
    {
        $value = is_string($value) ? trim($value) : $value;

        if ($value === null || $value === '') {
            return null;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $value) === 1) {
            return Carbon::parse($value)->toDateTimeString();
        }

        return Carbon::parse($workDate . ' ' . $value)->toDateTimeString();
    }

    /* ===== 一覧用 ===== */

    public function buildList(Collection $requests): array
    {
        return $requests
            ->map(function ($req) {
                return [
                    'id' => $req->id,
                    'attendance_id' => $req->attendance_id,
                    'status' => $req->status,
                    'staff_name' => $req->requesterUser->name,
                    'target_date' => $req->attendance->work_date,
                    'reason' => $req->payload?->after_attendance['note'] ?? '',
                    'requested_at' => $req->created_at,
                ];
            })
            ->sort(function ($a, $b) {
                if ($a['target_date'] == $b['target_date']) {
                    return $a['requested_at'] <=> $b['requested_at'];
                }
                return $a['target_date'] <=> $b['target_date'];
            })
            ->values()
            ->all();
    }

    /* ===== 申請作成 ===== */

    public function createRequest(
        Attendance $attendance,
        array $beforeAttendance,
        array $afterAttendance,
        array $beforeBreaks,
        array $afterBreaks,
        string $reason
    ): AttendanceChangeRequest {
        $request = AttendanceChangeRequest::create([
            'attendance_id' => $attendance->id,
            'requester_user_id' => $attendance->user_id,
            'status' => 'pending',
            'reason' => $reason,
        ]);

        AttendanceChangeRequestPayload::create([
            'attendance_change_request_id' => $request->id,
            'before_attendance' => $beforeAttendance,
            'after_attendance' => $afterAttendance,
            'before_breaks' => $beforeBreaks,
            'after_breaks' => $afterBreaks,
        ]);

        return $request;
    }

    /* ===== 承認処理 ===== */

    public function approve(AttendanceChangeRequest $request, User $admin): void
    {
        $payload = $request->payload;

        // 勤怠本体をafterデータで更新
        $attendance = $request->attendance;
        $after = $payload->after_attendance ?? [];
        $workDate = $attendance->work_date;
        $clockIn = $after['clock_in_at'] ?? null;
        $clockOut = $after['clock_out_at'] ?? null;
        $clockInAt = $this->normalizeDateTime($clockIn, $workDate);
        $clockOutAt = $this->normalizeDateTime($clockOut, $workDate);

        $attendance->update([
            'clock_in_at' => $clockInAt,
            'clock_out_at' => $clockOutAt,
            'note' => $after['note'] ?? null,
            'status' => 'approved',
        ]);

        // 休憩反映
        $existing = AttendanceBreak::where('attendance_id', $attendance->id)
            ->get()
            ->keyBy('id');

        $keepIds = [];

        foreach ($payload->after_breaks as $b) {
            // start_atもend_atも空欄ならスキップ（DBに残さない）
            if (empty($b['start_at']) && empty($b['end_at'])) {
                continue;
            }
            $start = $this->normalizeDateTime($b['start_at'] ?? null, $attendance->work_date);
            $end = $this->normalizeDateTime($b['end_at'] ?? null, $attendance->work_date);
            if (!empty($b['id']) && $existing->has($b['id'])) {
                $existing[$b['id']]->update([
                    'start_at' => $start,
                    'end_at' => $end,
                ]);
                $keepIds[] = (int) $b['id'];
            } elseif (empty($b['id'])) {
                $new = AttendanceBreak::create([
                    'attendance_id' => $attendance->id,
                    'start_at' => $start,
                    'end_at' => $end,
                ]);
                $keepIds[] = $new->id;
            }
        }

        AttendanceBreak::query()
            ->where('attendance_id', $attendance->id)
            ->when(count($keepIds) > 0, fn($q) => $q->whereNotIn('id', $keepIds))
            ->delete();

        $attendance->refresh()->load('breaks');
        app(AttendanceService::class)->recalculateAndSave($attendance);

        // 申請ステータス更新
        $request->update([
            'status' => 'approved',
            'reviewer_user_id' => $admin->id,
            'reviewed_at' => now(),
        ]);

        // 監査ログ
        AttendanceAudit::create([
            'attendance_id' => $attendance->id,
            'actor_user_id' => $admin->id,
            'action' => 'apply_change_request',
            'before_change' => [
                'attendance' => $payload->before_attendance,
                'breaks' => $payload->before_breaks,
            ],
            'after_change' => [
                'attendance' => $payload->after_attendance,
                'breaks' => $payload->after_breaks,
            ],
        ]);
    }

    /* ===== 却下（概念用） ===== */

    public function reject(AttendanceChangeRequest $request, User $admin, ?string $comment = null): void
    {
        $request->update([
            'status' => 'rejected',
            'reviewer_user_id' => $admin->id,
            'reviewed_at' => now(),
            'review_comment' => $comment,
        ]);
    }
}
