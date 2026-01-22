<?php

namespace Tests\Feature\Admin;

use App\Models\Attendance;
use App\Models\AttendanceBreak;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AdminAttendanceDetailTest extends TestCase
{
	use RefreshDatabase;

	private function makeAdmin(array $overrides = []): User
	{
		return User::factory()->create(array_merge([
			'role_id' => 1,
			'email_verified_at' => now(),
		], $overrides));
	}

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
			'clock_out_at' => Carbon::parse($workDate->toDateString() . ' 18:05:00'),
			'break_minutes' => 30,
			'work_minutes' => 505,
			'note' => '備考テスト',
			'status' => 'normal',
		]);
	}

	private function createBreak(Attendance $attendance, Carbon $workDate, string $start, string $end): AttendanceBreak
	{
		return AttendanceBreak::create([
			'attendance_id' => $attendance->id,
			'start_at' => Carbon::parse($workDate->toDateString() . ' ' . $start . ':00'),
			'end_at' => Carbon::parse($workDate->toDateString() . ' ' . $end . ':00'),
		]);
	}

	private function baseAdminStorePayload(Attendance $attendance, array $overrides = []): array
	{
		$payload = [
			'is_admin' => '1',
			'attendance_id' => $attendance->id,
			'reason' => '修正申請',
			'after_attendance' => [
				'clock_in_at' => '09:10',
				'clock_out_at' => '18:05',
				'note' => '修正理由',
			],
			'after_breaks' => [],
		];

		return array_replace_recursive($payload, $overrides);
	}

	/** 13. 勤怠詳細情報取得・修正機能(管理者) */
	/** 13-1. 勤怠詳細画面に表示されるデータが選択したものになっている */
	public function test_admin_detail_shows_selected_attendance_data(): void
	{
		$now = Carbon::create(2026, 1, 21, 10, 0, 0, config('app.timezone'));
		Carbon::setTestNow($now);

		$admin = $this->makeAdmin();
		$user = $this->makeUser(['name' => '田中 三郎']);
		$attendance = $this->createAttendance($user, $now->copy());
		$this->createBreak($attendance, $now->copy(), '12:10', '12:40');

		$this->actingAs($admin)
			->withSession(['is_admin_login' => true])
			->get(route('admin.attendance.show', $attendance->id))
			->assertOk()
			->assertSee('勤怠詳細')
			->assertSee('名前')
			->assertSee($user->name)
			->assertSee('日付')
			->assertSee('2026年')
			->assertSee('1月21日')
			->assertSee('出勤・退勤')
			->assertSee('09:10')
			->assertSee('18:05')
			->assertSee('休憩1')
			->assertSee('12:10')
			->assertSee('12:40')
			->assertSee('備考')
			->assertSee('備考テスト')
			->assertSee('修正');
	}

	/** 13-2. 出勤時間が退勤時間より後になっている場合、エラーメッセージが表示される */
	public function test_admin_clock_in_after_clock_out_shows_error_message(): void
	{
		$now = Carbon::create(2026, 1, 21, 10, 0, 0, config('app.timezone'));
		Carbon::setTestNow($now);

		$admin = $this->makeAdmin();
		$user = $this->makeUser();
		$attendance = $this->createAttendance($user, $now->copy());

		$payload = $this->baseAdminStorePayload($attendance, [
			'after_attendance' => [
				'clock_in_at' => '19:00',
				'clock_out_at' => '18:00',
				'note' => '修正理由',
			],
		]);

		$this->actingAs($admin)
			->withSession(['is_admin_login' => true])
			->from(route('admin.attendance.show', $attendance->id))
			->followingRedirects()
			->post(route('stamp_correction_request.store'), $payload)
			->assertOk()
			->assertSee('出勤時間もしくは退勤時間が不適切な値です');
	}

	/** 13-3. 休憩開始時間が退勤時間より後になっている場合、エラーメッセージが表示される */
	public function test_admin_break_start_after_clock_out_shows_error_message(): void
	{
		$now = Carbon::create(2026, 1, 21, 10, 0, 0, config('app.timezone'));
		Carbon::setTestNow($now);

		$admin = $this->makeAdmin();
		$user = $this->makeUser();
		$attendance = $this->createAttendance($user, $now->copy());

		$payload = $this->baseAdminStorePayload($attendance, [
			'after_attendance' => [
				'clock_in_at' => '09:00',
				'clock_out_at' => '18:00',
				'note' => '修正理由',
			],
			'after_breaks' => [
				['start_at' => '19:00', 'end_at' => '19:10'],
			],
		]);

		$this->actingAs($admin)
			->withSession(['is_admin_login' => true])
			->from(route('admin.attendance.show', $attendance->id))
			->followingRedirects()
			->post(route('stamp_correction_request.store'), $payload)
			->assertOk()
			->assertSee('休憩時間が不適切な値です');
	}

	/** 13-4. 休憩終了時間が退勤時間より後になっている場合、エラーメッセージが表示される */
	public function test_admin_break_end_after_clock_out_shows_error_message(): void
	{
		$now = Carbon::create(2026, 1, 21, 10, 0, 0, config('app.timezone'));
		Carbon::setTestNow($now);

		$admin = $this->makeAdmin();
		$user = $this->makeUser();
		$attendance = $this->createAttendance($user, $now->copy());

		$payload = $this->baseAdminStorePayload($attendance, [
			'after_attendance' => [
				'clock_in_at' => '09:00',
				'clock_out_at' => '18:00',
				'note' => '修正理由',
			],
			'after_breaks' => [
				['start_at' => '17:30', 'end_at' => '18:10'],
			],
		]);

		$this->actingAs($admin)
			->withSession(['is_admin_login' => true])
			->from(route('admin.attendance.show', $attendance->id))
			->followingRedirects()
			->post(route('stamp_correction_request.store'), $payload)
			->assertOk()
			->assertSee('休憩時間もしくは退勤時間が不適切な値です');
	}

	/** 13-5. 備考欄が未入力の場合のエラーメッセージが表示される */
	public function test_admin_note_required_shows_error_message(): void
	{
		$now = Carbon::create(2026, 1, 21, 10, 0, 0, config('app.timezone'));
		Carbon::setTestNow($now);

		$admin = $this->makeAdmin();
		$user = $this->makeUser();
		$attendance = $this->createAttendance($user, $now->copy());

		$payload = $this->baseAdminStorePayload($attendance, [
			'after_attendance' => [
				'clock_in_at' => '09:00',
				'clock_out_at' => '18:00',
				'note' => '',
			],
		]);

		$this->actingAs($admin)
			->withSession(['is_admin_login' => true])
			->from(route('admin.attendance.show', $attendance->id))
			->followingRedirects()
			->post(route('stamp_correction_request.store'), $payload)
			->assertOk()
			->assertSee('備考を記入してください');
	}
}

