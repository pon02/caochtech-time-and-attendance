<?php

namespace Tests\Feature\Attendance;

use App\Models\Attendance;
use App\Models\AttendanceBreak;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AttendanceDetailTest extends TestCase
{
	use RefreshDatabase;

	private function makeUser(array $overrides = []): User
	{
		return User::factory()->create(array_merge([
			'role_id' => 2,
			'email_verified_at' => now(),
		], $overrides));
	}

	private function createAttendance(User $user, Carbon $workDate): Attendance
	{
		return Attendance::create([
			'user_id' => $user->id,
			'work_date' => $workDate->toDateString(),
			'clock_in_at' => Carbon::parse($workDate->toDateString() . ' 09:10:00'),
			'clock_out_at' => Carbon::parse($workDate->toDateString() . ' 18:20:00'),
			'break_minutes' => 60,
			'work_minutes' => 490,
			'note' => null,
			'status' => 'normal',
		]);
	}

	/** 10.勤怠詳細情報取得機能(一般ユーザー) */
	/** 10-1.勤怠詳細画面の「名前」がログインユーザーの氏名になっている */
	public function test_detail_name_is_login_user_name(): void
	{
		$now = Carbon::create(2026, 1, 21, 10, 0, 0, config('app.timezone'));
		Carbon::setTestNow($now);

		$user = $this->makeUser(['name' => '山田 太郎']);
		$attendance = $this->createAttendance($user, $now->copy());

		$this->actingAs($user)
			->get(route('attendance.detail', $attendance->id))
			->assertOk()
			->assertSee('名前')
			->assertSee($user->name);
	}

	/** 10-2.勤怠詳細画面の「日付」が選択した日付になっている */
	public function test_detail_date_is_selected_date(): void
	{
		$now = Carbon::create(2026, 1, 21, 10, 0, 0, config('app.timezone'));
		Carbon::setTestNow($now);

		$user = $this->makeUser();
		$workDate = Carbon::create(2026, 1, 20, 0, 0, 0, config('app.timezone'));
		$attendance = $this->createAttendance($user, $workDate);

		$this->actingAs($user)
			->get(route('attendance.detail', $attendance->id))
			->assertOk()
			->assertSee('日付')
			->assertSee('2026年')
			->assertSee('1月20日');
	}

	/** 10-3.「出勤・退勤」にて記されている時間がログインユーザーの打刻と一致している */
	public function test_detail_clock_in_out_matches_attendance(): void
	{
		$now = Carbon::create(2026, 1, 21, 10, 0, 0, config('app.timezone'));
		Carbon::setTestNow($now);

		$user = $this->makeUser();
		$workDate = Carbon::create(2026, 1, 21, 0, 0, 0, config('app.timezone'));

		$attendance = Attendance::create([
			'user_id' => $user->id,
			'work_date' => $workDate->toDateString(),
			'clock_in_at' => Carbon::parse($workDate->toDateString() . ' 08:45:00'),
			'clock_out_at' => Carbon::parse($workDate->toDateString() . ' 19:05:00'),
			'break_minutes' => 0,
			'work_minutes' => 0,
			'note' => null,
			'status' => 'normal',
		]);

		$this->actingAs($user)
			->get(route('attendance.detail', $attendance->id))
			->assertOk()
			->assertSee('出勤・退勤')
			->assertSee('08:45')
			->assertSee('19:05');
	}

	/** 10-4.「休憩」にて記されている時間がログインユーザーの打刻と一致している */
	public function test_detail_break_times_match_attendance_breaks(): void
	{
		$now = Carbon::create(2026, 1, 21, 10, 0, 0, config('app.timezone'));
		Carbon::setTestNow($now);

		$user = $this->makeUser();
		$workDate = Carbon::create(2026, 1, 21, 0, 0, 0, config('app.timezone'));
		$attendance = $this->createAttendance($user, $workDate);

		AttendanceBreak::create([
			'attendance_id' => $attendance->id,
			'start_at' => Carbon::parse($workDate->toDateString() . ' 12:10:00'),
			'end_at' => Carbon::parse($workDate->toDateString() . ' 12:40:00'),
		]);

		AttendanceBreak::create([
			'attendance_id' => $attendance->id,
			'start_at' => Carbon::parse($workDate->toDateString() . ' 15:00:00'),
			'end_at' => Carbon::parse($workDate->toDateString() . ' 15:20:00'),
		]);

		$this->actingAs($user)
			->get(route('attendance.detail', $attendance->id))
			->assertOk()
			->assertSee('休憩1')
			->assertSee('12:10')
			->assertSee('12:40')
			->assertSee('休憩2')
			->assertSee('15:00')
			->assertSee('15:20');
	}
}

