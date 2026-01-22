<?php

namespace Tests\Feature\Attendance\TimeRecord;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ClockInTest extends TestCase
{
    use RefreshDatabase;

    /** 6.出勤機能 */
    /** 6-1.出勤ボタンが正しく機能する */
    public function test_clock_in_button_works_and_status_changes(): void
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
            ->assertSee('出勤')
            ->assertSee('勤務外');

        $this->actingAs($user)
            ->post(route('attendance.stamp'), ['type' => 'clock_in'])
            ->assertRedirect(route('attendance.time_record'));

        $this->actingAs($user)
            ->get(route('attendance.time_record'))
            ->assertOk()
            ->assertSee('出勤中');
    }

    /** 6-2.出勤は一日一回のみできる */
    public function test_clock_in_button_is_not_displayed_after_finished(): void
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
            ->assertSee('退勤済')
            ->assertDontSee('出勤');
    }

    /** 6-3.出勤時刻が勤怠一覧画面で確認できる */
    public function test_clock_in_time_is_visible_on_attendance_index(): void
    {
        $now = Carbon::create(2026, 1, 21, 9, 0, 0, config('app.timezone'));
        Carbon::setTestNow($now);

        $user = User::factory()->create([
            'role_id' => 2,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->post(route('attendance.stamp'), ['type' => 'clock_in'])
            ->assertRedirect(route('attendance.time_record'));

        $weekdays = ['日', '月', '火', '水', '木', '金', '土'];
        $expectedDateCell = $now->format('m/d') . '(' . $weekdays[$now->dayOfWeek] . ')';
        $expectedClockIn = $now->format('H:i');

        $this->actingAs($user)
            ->get(route('attendance.index'))
            ->assertOk()
            ->assertSee($expectedDateCell)
            ->assertSee($expectedClockIn);
    }
}
