<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\AttendanceChangeRequest;
use App\Services\StampCorrectionRequestService;
use App\Http\Requests\AttendanceDetailRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StampCorrectionRequestController extends Controller
{
    public function __construct(private StampCorrectionRequestService $service) {}

    public function index(Request $request)
    {
        $user = $request->user();

        // 管理者ログイン時のみ全件表示、それ以外は自分の申請のみ
        $isAdmin = (int) $user->role_id === 1 && session('is_admin_login') === true;

        $query = AttendanceChangeRequest::with(['attendance', 'requesterUser'])
            ->orderByDesc('created_at');

        if (!$isAdmin) {
            $query->where('requester_user_id', $user->id);
        }

        $pending = (clone $query)->where('status', 'pending')->get();
        $approved = (clone $query)->where('status', 'approved')->get();

        return view('stamp_correction_request.index', [
            'pending' => $this->service->buildList($pending),
            'approved' => $this->service->buildList($approved),
            'isAdmin' => $isAdmin,
        ]);
    }

    public function store(AttendanceDetailRequest $request)
    {
        DB::transaction(function () use ($request) {
            $attendance = Attendance::findOrFail($request->input('attendance_id'));

            $isAdmin = $request->input('is_admin') == 1;

            // clock_in_at/clock_out_atをdatetime形式に変換
            $workDate = $attendance->work_date;
            $after = $request->input('after_attendance');
            $clockIn = $after['clock_in_at'] ?? null;
            $clockOut = $after['clock_out_at'] ?? null;
            $clockInAt = $clockIn ? $workDate . ' ' . $clockIn . ':00' : null;
            $clockOutAt = $clockOut ? $workDate . ' ' . $clockOut . ':00' : null;

            // 管理者操作の場合は即時承認＆勤怠更新
            if ($isAdmin) {
                $attendance->update([
                    'clock_in_at' => $clockInAt,
                    'clock_out_at' => $clockOutAt,
                    'note' => $after['note'] ?? null,
                    'status' => 'approved',
                ]);
                $changeRequest = $this->service->createRequest(
                    attendance: $attendance,
                    beforeAttendance: $request->input('before_attendance', []),
                    afterAttendance: $request->input('after_attendance', []),
                    beforeBreaks: $request->input('before_breaks', []),
                    afterBreaks: $request->input('after_breaks', []),
                    reason: $request->input('reason')
                );
                $this->service->approve($changeRequest, $request->user());
            } else {
                $attendance->update(['status' => 'pending']);
                $this->service->createRequest(
                    attendance: $attendance,
                    beforeAttendance: $request->input('before_attendance', []),
                    afterAttendance: $request->input('after_attendance', []),
                    beforeBreaks: $request->input('before_breaks', []),
                    afterBreaks: $request->input('after_breaks', []),
                    reason: $request->input('reason')
                );
            }
        });

        // 管理者は管理者詳細画面へ、一般は申請一覧へ
        if ($request->input('is_admin') == 1) {
            return redirect()->route('admin.attendance.show', $request->input('attendance_id'))->with('message', '管理者として即時反映しました。');
        }
        return redirect()->route('stamp_correction_request.list');
    }

    public function approveForm(AttendanceChangeRequest $attendance_change_request_id)
    {
        $attendance_change_request_id->load(['attendance.breaks', 'payload', 'requesterUser']);

        return view('admin.approve', [
            'changeRequest' => $attendance_change_request_id,
            'attendance' => $attendance_change_request_id->attendance,
        ]);
    }

    public function approve(Request $request, AttendanceChangeRequest $attendance_change_request_id)
    {
        DB::transaction(function () use ($request, $attendance_change_request_id) {
            $this->service->approve($attendance_change_request_id, $request->user());
        });

        return redirect()
            ->route('stamp_correction_request.approve', [
                'attendance_change_request_id' => $attendance_change_request_id->id,
            ])
            ->with('message', '承認しました。');
    }

    /* 却下（今回は未使用だが概念として） */
    public function reject(Request $request, AttendanceChangeRequest $attendance_change_request_id)
    {
        DB::transaction(function () use ($request, $attendance_change_request_id) {
            $this->service->reject(
                request: $attendance_change_request_id,
                admin: $request->user(),
                comment: $request->input('comment')
            );
        });

        return redirect()->route('stamp_correction_request.index');
    }
}
