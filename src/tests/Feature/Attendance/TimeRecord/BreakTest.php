<?php

namespace Tests\Feature\Attendance\TimeRecord;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class BreakTest extends TestCase
{
    use RefreshDatabase;

    /** 7.休憩機能 */
    /** 7-1.休憩ボタンが正しく機能する */
    public function test_break_start_button_works_and_status_changes_to_on_break(): void
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

        $this->actingAs($user)
            ->get(route('attendance.time_record'))
            ->assertOk()
            ->assertSee('出勤中')
            ->assertSee('休憩入');

        $this->actingAs($user)
            ->post(route('attendance.stamp'), ['type' => 'break_start'])
            ->assertRedirect(route('attendance.time_record'));

        $this->actingAs($user)
            ->get(route('attendance.time_record'))
            ->assertOk()
            ->assertSee('休憩中')
            ->assertSee('休憩戻');
    }

    /** 7-2.休憩は一日に何回でもできる */
    public function test_break_can_be_started_multiple_times_in_a_day(): void
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
            ->post(route('attendance.stamp'), ['type' => 'break_start'])
            ->assertRedirect(route('attendance.time_record'));

        Carbon::setTestNow($start->copy()->addMinutes(10));
        $this->actingAs($user)
            ->post(route('attendance.stamp'), ['type' => 'break_end'])
            ->assertRedirect(route('attendance.time_record'));

        $this->actingAs($user)
            ->get(route('attendance.time_record'))
            ->assertOk()
            ->assertSee('出勤中')
            ->assertSee('休憩入');
    }

    /** 7-3.休憩戻ボタンが正しく機能する */
    public function test_break_end_button_works_and_status_changes_to_working(): void
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
            ->post(route('attendance.stamp'), ['type' => 'break_start'])
            ->assertRedirect(route('attendance.time_record'));

        $this->actingAs($user)
            ->get(route('attendance.time_record'))
            ->assertOk()
            ->assertSee('休憩戻')
            ->assertSee('休憩中');

        Carbon::setTestNow($start->copy()->addMinutes(5));
        $this->actingAs($user)
            ->post(route('attendance.stamp'), ['type' => 'break_end'])
            ->assertRedirect(route('attendance.time_record'));

        $this->actingAs($user)
            ->get(route('attendance.time_record'))
            ->assertOk()
            ->assertSee('出勤中')
            ->assertSee('休憩入');
    }

    /** 7-4.休憩戻は一日に何回でもできる */
    public function test_break_end_button_is_displayed_again_on_second_break(): void
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
            ->post(route('attendance.stamp'), ['type' => 'break_start'])
            ->assertRedirect(route('attendance.time_record'));

        Carbon::setTestNow($start->copy()->addMinutes(10));
        $this->actingAs($user)
            ->post(route('attendance.stamp'), ['type' => 'break_end'])
            ->assertRedirect(route('attendance.time_record'));

        Carbon::setTestNow($start->copy()->addMinutes(20));
        $this->actingAs($user)
            ->post(route('attendance.stamp'), ['type' => 'break_start'])
            ->assertRedirect(route('attendance.time_record'));

        $this->actingAs($user)
            ->get(route('attendance.time_record'))
            ->assertOk()
            ->assertSee('休憩中')
            ->assertSee('休憩戻');
    }

    /** 7-5.休憩時刻が勤怠一覧画面で確認できる */
    public function test_break_time_is_visible_on_attendance_index(): void
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
            ->post(route('attendance.stamp'), ['type' => 'break_start'])
            ->assertRedirect(route('attendance.time_record'));

        Carbon::setTestNow($start->copy()->addMinutes(30));
        $this->actingAs($user)
            ->post(route('attendance.stamp'), ['type' => 'break_end'])
            ->assertRedirect(route('attendance.time_record'));

        $weekdays = ['日', '月', '火', '水', '木', '金', '土'];
        $expectedDateCell = $start->format('m/d') . '(' . $weekdays[$start->dayOfWeek] . ')';
        $expectedBreak = '00:30';

        $this->actingAs($user)
            ->get(route('attendance.index'))
            ->assertOk()
            ->assertSee($expectedDateCell)
            ->assertSee($expectedBreak);
    }
}
