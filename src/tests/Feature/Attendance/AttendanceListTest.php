<?php

namespace Tests\Feature\Attendance;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AttendanceListTest extends TestCase
{
	use RefreshDatabase;

	private function makeUser(): User
	{
		return User::factory()->create([
			'role_id' => 2,
			'email_verified_at' => now(),
		]);
	}

	private function createAttendance(
		User $user,
		Carbon $workDate,
		string $clockIn,
		string $clockOut,
		int $breakMinutes,
		int $workMinutes
	): Attendance {
		return Attendance::create([
			'user_id' => $user->id,
			'work_date' => $workDate->toDateString(),
			'clock_in_at' => Carbon::parse($workDate->toDateString() . ' ' . $clockIn . ':00'),
			'clock_out_at' => Carbon::parse($workDate->toDateString() . ' ' . $clockOut . ':00'),
			'break_minutes' => $breakMinutes,
			'work_minutes' => $workMinutes,
			'note' => null,
			'status' => 'normal',
		]);
	}

	/** 9.勤怠一覧情報取得機能（一般ユーザー） */
	/** 9-1.自分が行った勤怠情報が全て表示されている */
	public function test_own_attendances_are_displayed_on_index(): void
	{
		$now = Carbon::create(2026, 1, 21, 10, 0, 0, config('app.timezone'));
		Carbon::setTestNow($now);

		$user = $this->makeUser();
		$other = $this->makeUser();

		$a1 = $this->createAttendance($user, $now->copy()->startOfMonth()->addDays(1), '09:10', '18:05', 30, 505);
		$a2 = $this->createAttendance($user, $now->copy()->startOfMonth()->addDays(3), '08:55', '17:40', 45, 520);

		// 他人の勤怠（表示されないことも一応担保）
		$this->createAttendance($other, $now->copy()->startOfMonth()->addDays(1), '06:00', '15:00', 0, 540);

		$response = $this->actingAs($user)->get(route('attendance.index'));
		$response->assertOk();

		$response
			->assertSee('09:10')
			->assertSee('18:05')
			->assertSee('00:30')
			->assertSee('08:55')
			->assertSee('17:40')
			->assertSee('00:45');

		$response
			->assertSee(route('attendance.detail', $a1->id))
			->assertSee(route('attendance.detail', $a2->id));

		$response->assertDontSee('06:00');
		$response->assertDontSee('15:00');
	}

	/** 9-2.勤怠一覧画面に遷移した際に現在の月が表示される */
	public function test_current_month_is_displayed_on_index(): void
	{
		$now = Carbon::create(2026, 1, 21, 10, 0, 0, config('app.timezone'));
		Carbon::setTestNow($now);

		$user = $this->makeUser();

		$this->actingAs($user)
			->get(route('attendance.index'))
			->assertOk()
			->assertSee($now->format('Y/m'));
	}

	/** 9-3.「前月」を押下した時に表示月の前月の情報が表示される */
	public function test_prev_month_is_displayed_when_month_query_is_prev(): void
	{
		$now = Carbon::create(2026, 1, 21, 10, 0, 0, config('app.timezone'));
		Carbon::setTestNow($now);

		$user = $this->makeUser();

		$prevMonth = $now->copy()->startOfMonth()->subMonth();
		$this->createAttendance($user, $prevMonth->copy()->addDays(2), '09:01', '18:02', 15, 526);

		$this->actingAs($user)
			->get(route('attendance.index', ['month' => $prevMonth->format('Y-m')]))
			->assertOk()
			->assertSee($prevMonth->format('Y/m'))
			->assertSee('09:01')
			->assertSee('18:02');
	}

	/** 9-4.「翌月」を押下した時に表示月の翌月の情報が表示される */
	public function test_next_month_is_displayed_when_month_query_is_next(): void
	{
		$now = Carbon::create(2026, 1, 21, 10, 0, 0, config('app.timezone'));
		Carbon::setTestNow($now);

		$user = $this->makeUser();

		$nextMonth = $now->copy()->startOfMonth()->addMonth();
		$this->createAttendance($user, $nextMonth->copy()->addDays(4), '10:11', '19:12', 0, 541);

		$this->actingAs($user)
			->get(route('attendance.index', ['month' => $nextMonth->format('Y-m')]))
			->assertOk()
			->assertSee($nextMonth->format('Y/m'))
			->assertSee('10:11')
			->assertSee('19:12');
	}

	/** 9-5.「詳細」を押下すると、その日の勤怠詳細画面に遷移する */
	public function test_detail_link_navigates_to_attendance_detail(): void
	{
		$now = Carbon::create(2026, 1, 21, 10, 0, 0, config('app.timezone'));
		Carbon::setTestNow($now);

		$user = $this->makeUser();

		$attendance = $this->createAttendance($user, $now->copy(), '09:00', '18:00', 60, 480);

		$this->actingAs($user)
			->get(route('attendance.index'))
			->assertOk()
			->assertSee(route('attendance.detail', $attendance->id));

		$this->actingAs($user)
			->get(route('attendance.detail', $attendance->id))
			->assertOk()
			->assertSee('勤怠詳細');
	}
}

