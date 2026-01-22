<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\AttendanceBreak;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    public function __construct(private AttendanceService $service) {}

    public function show(Request $request)
    {
        $user = $request->user();
        $today = Carbon::today()->toDateString();

        $attendance = Attendance::with('breaks')
            ->where('user_id', $user->id)
            ->where('work_date', $today)
            ->first();

        if (!$attendance) {
            return view('attendance.time_record', [
                'now' => now(),
                'attendance' => null,
                'status' => '勤務外',
                'availableActions' => [
                    'clock_in' => true,
                    'break_start' => false,
                    'break_end' => false,
                    'clock_out' => false,
                ],
            ]);
        }

        return view('attendance.time_record', [
            'now' => now(),
            'attendance' => $attendance,
            'status' => $this->service->calcStatus($attendance),
            'availableActions' => [
                'clock_in' => $attendance->clock_in_at === null,
                'break_start' => $this->service->canBreakStart($attendance),
                'break_end' => $this->service->canBreakEnd($attendance),
                'clock_out' => $this->service->canClockOut($attendance),
            ],
        ]);
    }

    public function stamp(Request $request)
    {
        $type = $request->input('type');
        $user = $request->user();
        $today = Carbon::today()->toDateString();
        $now = now();

        DB::transaction(function () use ($user, $today, $now, $type) {
            $attendance = Attendance::where('user_id', $user->id)
                ->where('work_date', $today)
                ->lockForUpdate()
                ->first();

            if (!$attendance) {
                $attendance = Attendance::create([
                    'user_id' => $user->id,
                    'work_date' => $today,
                    'clock_in_at' => null,
                    'clock_out_at' => null,
                    'note' => null,
                    'status' => 'normal',
                ]);
            }

            $attendance->load('breaks');

            match ($type) {
                'clock_in' => $attendance->update(['clock_in_at' => $now]),

                'break_start' => AttendanceBreak::create([
                    'attendance_id' => $attendance->id,
                    'start_at' => $now,
                    'end_at' => null,
                ]),

                'break_end' => optional(
                    $attendance->breaks->whereNull('end_at')->sortByDesc('start_at')->first()
                )->update(['end_at' => $now]),

                'clock_out' => $attendance->update(['clock_out_at' => $now]),
            };

            $attendance->refresh()->load('breaks');
            app(AttendanceService::class)->recalculateAndSave($attendance);
        });

        return redirect()->route('attendance.time_record');
    }

    public function index(Request $request)
    {
        $user = $request->user();
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
            $attendance = $attendanceMap->get($cursor->toDateString());

            $days[] = [
                'date' => $cursor->copy(),
                'attendance' => $attendance,
                'break_minutes' => $attendance ? $attendance->break_minutes : null,
                'work_minutes' => $attendance ? $attendance->work_minutes : null,
            ];

            $cursor->addDay();
        }

        return view('attendance.index', [
            'month' => $month,
            'prevMonth' => $month->copy()->subMonth()->format('Y-m'),
            'nextMonth' => $month->copy()->addMonth()->format('Y-m'),
            'days' => $days,
        ]);
    }

    public function detail(Request $request, Attendance $attendance)
    {
        abort_if($attendance->user_id !== $request->user()->id, 403);

        $attendance->load('breaks');

        return view('attendance.detail', [
            'attendance' => $attendance,
            'staffName' => $request->user()->name,
            'breaksForForm' => $this->service->makeBreakFormRows($attendance),
        ]);
    }
}
