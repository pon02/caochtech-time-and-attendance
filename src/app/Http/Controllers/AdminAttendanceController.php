<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AttendanceBreak;
use App\Models\User;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminAttendanceController extends Controller
{
    public function __construct(private AttendanceService $service) {}

    public function index(Request $request)
    {
        $date = $request->query('date')
            ? Carbon::parse($request->query('date'))->startOfDay()
            : Carbon::today();

        $attendances = Attendance::query()
            ->with(['user'])
            ->where('work_date', $date->toDateString())
            ->orderBy(User::select('name')->whereColumn('users.id', 'attendances.user_id'))
            ->get();

        $rows = $attendances->map(function (Attendance $a) {
            return [
                'id' => $a->id,
                'staff_name' => $a->user->name,
                'clock_in_at' => $a->clock_in_at,
                'clock_out_at' => $a->clock_out_at,
                'break_minutes' => $this->service->storedBreakMinutes($a),
                'work_minutes' => $this->service->storedWorkMinutes($a),
            ];
        });

        return view('admin.index', [
            'date' => $date,
            'prevDate' => $date->copy()->subDay()->toDateString(),
            'nextDate' => $date->copy()->addDay()->toDateString(),
            'rows' => $rows,
        ]);
    }

    public function show(Request $request, Attendance $attendance)
    {
        $attendance->load(['breaks' => fn($q) => $q->orderBy('start_at'), 'user']);

        return view('admin.detail', [
            'attendance' => $attendance,
        ]);
    }

    public function update(\App\Http\Requests\AttendanceDetailRequest $request, Attendance $attendance)
    {
        DB::transaction(function () use ($request, $attendance) {
            $attendance->lockForUpdate();
            $attendance->update([
                'clock_in_at' => $request->input('clock_in_at'),
                'clock_out_at' => $request->input('clock_out_at'),
                'note' => $request->input('note'),
            ]);

            $breakInputs = $request->input('after_breaks', []);

            $existing = AttendanceBreak::query()
                ->where('attendance_id', $attendance->id)
                ->get()
                ->keyBy('id');

            $keepIds = [];

            foreach ($breakInputs as $b) {
                $id = $b['id'] ?? null;
                $start = $b['start_at'] ?? null;
                $end = $b['end_at'] ?? null;

                // 空行は無視（追加しない）
                if (!$start && !$end) {
                    continue;
                }

                if ($id && $existing->has($id)) {
                    $existing[$id]->update(['start_at' => $start, 'end_at' => $end]);
                    $keepIds[] = (int) $id;
                    continue;
                }

                $new = AttendanceBreak::query()->create([
                    'attendance_id' => $attendance->id,
                    'start_at' => $start,
                    'end_at' => $end,
                ]);
                $keepIds[] = $new->id;
            }

            AttendanceBreak::query()
                ->where('attendance_id', $attendance->id)
                ->when(count($keepIds) > 0, fn($q) => $q->whereNotIn('id', $keepIds))
                ->delete();
        });

        $attendance->refresh()->load('breaks');
        $this->service->recalculateAndSave($attendance);

        return redirect()
            ->route('admin.attendance.show', $attendance->id)
            ->with('message', '更新しました。');
    }

    public function staffMonthly(Request $request, User $user)
    {
        $month = $request->query('month')
            ? Carbon::createFromFormat('Y-m', $request->query('month'))->startOfMonth()
            : now()->startOfMonth();

        $start = $month->copy()->startOfMonth();
        $end = $month->copy()->endOfMonth();

        $attendanceMap = Attendance::query()
            ->where('user_id', $user->id)
            ->whereBetween('work_date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->keyBy('work_date');

        $days = [];
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $a = $attendanceMap->get($cursor->toDateString());
            $days[] = [
                'date' => $cursor->copy(),
                'attendance' => $a,
                'break_minutes' => $a ? $this->service->storedBreakMinutes($a) : null,
                'work_minutes' => $a ? $this->service->storedWorkMinutes($a) : null,
            ];
            $cursor->addDay();
        }

        return view('admin.staff_monthly_list', [
            'user' => $user,
            'month' => $month,
            'prevMonth' => $month->copy()->subMonth()->format('Y-m'),
            'nextMonth' => $month->copy()->addMonth()->format('Y-m'),
            'days' => $days,
        ]);
    }

    /** * 1ヶ月分CSV出力 */
    public function exportCsv(Request $request, User $user): StreamedResponse
    {
        $month = $request->query('month')
            ? Carbon::createFromFormat('Y-m', $request->query('month'))->startOfMonth()
            : now()->startOfMonth();

        $start = $month->copy()->startOfMonth();
        $end = $month->copy()->endOfMonth();

        $attendances = Attendance::query()
            ->where('user_id', $user->id)
            ->whereBetween('work_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('work_date')
            ->get();

        $filename = sprintf(
            '月次勤怠_%s年%s月（%s）.csv',
            $month->format('Y'),
            $month->format('n'),
            $user->name
        );

        return response()->streamDownload(function () use ($attendances) {
            $out = fopen('php://output', 'w');
            // Excel想定の文字化け対策
            fprintf($out, "\xEF\xBB\xBF");

            fputcsv($out, ['日付', '出勤', '退勤', '休憩(分)', '勤務(分)', '備考']);

            foreach ($attendances as $a) {
                fputcsv($out, [
                    $a->work_date,
                    $a->clock_in_at,
                    $a->clock_out_at,
                    $this->service->storedBreakMinutes($a),
                    $this->service->storedWorkMinutes($a),
                    $a->note,
                ]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function stampCorrectionRequestList()
    {
        $pending = \App\Models\AttendanceChangeRequest::with(['attendance', 'requesterUser'])
            ->where('status', 'pending')
            ->orderByDesc('created_at')
            ->get();
        $approved = \App\Models\AttendanceChangeRequest::with(['attendance', 'requesterUser'])
            ->where('status', 'approved')
            ->orderByDesc('created_at')
            ->get();
        return view('admin.stamp_correction_request', [
            'pending' => app(\App\Services\StampCorrectionRequestService::class)->buildList($pending),
            'approved' => app(\App\Services\StampCorrectionRequestService::class)->buildList($approved),
        ]);
    }
}
