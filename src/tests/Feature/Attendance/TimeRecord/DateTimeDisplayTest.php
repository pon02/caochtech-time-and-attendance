<?php

namespace Tests\Feature\Attendance\TimeRecord;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DateTimeDisplayTest extends TestCase
{
    use RefreshDatabase;

    /** 4.日時取得機能 */
    /** 4-1.現在の日時情報がUIと同じ形式で出力されている */
    public function test_current_datetime_is_displayed_in_ui_format(): void
    {
        $now = Carbon::create(2026, 1, 21, 9, 5, 0, config('app.timezone'));
        Carbon::setTestNow($now);

        $user = User::factory()->create([
            'role_id' => 2,
            'email_verified_at' => now(),
        ]);

        $weekdays = ['日', '月', '火', '水', '木', '金', '土'];
        $expectedDate = $now->format('Y年n月j日') . '(' . $weekdays[$now->dayOfWeek] . ')';
        $expectedTime = $now->format('H:i');

        $this->actingAs($user)
            ->get(route('attendance.time_record'))
            ->assertOk()
            ->assertSee($expectedDate)
            ->assertSee($expectedTime);
    }
}
