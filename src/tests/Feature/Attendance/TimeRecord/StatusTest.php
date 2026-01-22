<?php

namespace Tests\Feature\Attendance\TimeRecord;

use App\Models\Attendance;
use App\Models\AttendanceBreak;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class StatusTest extends TestCase
{
    use RefreshDatabase;

    /** 5.ステータス確認機能 */
    /** 5-1.勤務外の場合、勤怠ステータスが正しく表示される */
    public function test_status_is_off_duty_when_user_has_no_attendance_today(): void
    {
        $now = Carbon::create(2026, 1, 21, 9, 0, 0, config('app.timezone'));
        Carbon::setTestNow($now);

        $user = User::factory()->create([
            'role_id' => 2,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('attendance.time_record'))
            ->assertOk()
            ->assertSee('勤務外');
    }

    /** 5-2.出勤中の場合、勤怠ステータスが正しく表示される */
    public function test_status_is_working_when_user_has_clocked_in_and_not_on_break(): void
    {
        $now = Carbon::create(2026, 1, 21, 9, 0, 0, config('app.timezone'));
        Carbon::setTestNow($now);

        $user = User::factory()->create([
            'role_id' => 2,
            'email_verified_at' => now(),
        ]);

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => $now->toDateString(),
            'clock_in_at' => $now->copy()->subMinutes(30),
            'clock_out_at' => null,
            'status' => 'normal',
        ]);

        $this->actingAs($user)
            ->get(route('attendance.time_record'))
            ->assertOk()
            ->assertSee('出勤中');
    }

    /** 5-3.休憩中の場合、勤怠ステータスが正しく表示される */
    public function test_status_is_on_break_when_user_has_open_break(): void
    {
        $now = Carbon::create(2026, 1, 21, 9, 0, 0, config('app.timezone'));
        Carbon::setTestNow($now);

        $user = User::factory()->create([
            'role_id' => 2,
            'email_verified_at' => now(),
        ]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => $now->toDateString(),
            'clock_in_at' => $now->copy()->subMinutes(30),
            'clock_out_at' => null,
            'status' => 'normal',
        ]);

        AttendanceBreak::create([
            'attendance_id' => $attendance->id,
            'start_at' => $now->copy()->subMinutes(10),
            'end_at' => null,
        ]);

        $this->actingAs($user)
            ->get(route('attendance.time_record'))
            ->assertOk()
            ->assertSee('休憩中');
    }

    /** 5-4.退勤済の場合、勤怠ステータスが正しく表示される */
    public function test_status_is_finished_when_user_has_clocked_out(): void
    {
        $now = Carbon::create(2026, 1, 21, 18, 0, 0, config('app.timezone'));
        Carbon::setTestNow($now);

        $user = User::factory()->create([
            'role_id' => 2,
            'email_verified_at' => now(),
        ]);

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => $now->toDateString(),
            'clock_in_at' => $now->copy()->subHours(8),
            'clock_out_at' => $now,
            'status' => 'normal',
        ]);

        $this->actingAs($user)
            ->get(route('attendance.time_record'))
            ->assertOk()
            ->assertSee('退勤済');
    }
}
