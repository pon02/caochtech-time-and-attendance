<?php

namespace Tests\Feature\Attendance\TimeRecord;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ClockOutTest extends TestCase
{
    use RefreshDatabase;

    /** 8.退勤機能 */
    /** 8-1.退勤ボタンが正しく機能する */
    public function test_clock_out_button_works_and_status_changes_to_finished(): void
    {
        $start = Carbon::create(2026, 1, 21, 9, 0, 0, config('app.timezone'));
        Carbon::setTestNow($start);

        $user = User::factory()->create([
            'role_id' => 2,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->post(route('attendance.stamp'), ['type' => 'clock_in'])
            ->assertRedirect(route('attendance.time_record'));

        $this->actingAs($user)
            ->get(route('attendance.time_record'))
            ->assertOk()
            ->assertSee('出勤中')
            ->assertSee('退勤');

        Carbon::setTestNow($start->copy()->addHours(9)); // 18:00
        $this->actingAs($user)
            ->post(route('attendance.stamp'), ['type' => 'clock_out'])
            ->assertRedirect(route('attendance.time_record'));

        $this->actingAs($user)
            ->get(route('attendance.time_record'))
            ->assertOk()
            ->assertSee('退勤済');
    }

    /** 8-2.退勤時刻が勤怠一覧画面で確認できる */
    public function test_clock_out_time_is_visible_on_attendance_index(): void
    {
        $start = Carbon::create(2026, 1, 21, 9, 0, 0, config('app.timezone'));
        Carbon::setTestNow($start);

        $user = User::factory()->create([
            'role_id' => 2,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->post(route('attendance.stamp'), ['type' => 'clock_in'])
            ->assertRedirect(route('attendance.time_record'));

        $end = $start->copy()->addHours(9); // 18:00
        Carbon::setTestNow($end);
        $this->actingAs($user)
            ->post(route('attendance.stamp'), ['type' => 'clock_out'])
            ->assertRedirect(route('attendance.time_record'));

        $weekdays = ['日', '月', '火', '水', '木', '金', '土'];
        $expectedDateCell = $start->format('m/d') . '(' . $weekdays[$start->dayOfWeek] . ')';
        $expectedClockOut = $end->format('H:i');

        $this->actingAs($user)
            ->get(route('attendance.index'))
            ->assertOk()
            ->assertSee($expectedDateCell)
            ->assertSee($expectedClockOut);
    }
}
