<?php

namespace Tests\Feature\Attendance;

use App\Models\Attendance;
use App\Models\AttendanceChangeRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class StumpCorrectionRequestTest extends TestCase
{
	use RefreshDatabase;

	private function makeUser(array $overrides = []): User
	{
		return User::factory()->create(array_merge([
			'role_id' => 2,
			'email_verified_at' => now(),
		], $overrides));
	}

	private function makeAdmin(array $overrides = []): User
	{
		return User::factory()->create(array_merge([
			'role_id' => 1,
			'email_verified_at' => now(),
		], $overrides));
	}

	private function createAttendance(User $user, Carbon $workDate): Attendance
	{
		return Attendance::create([
			'user_id' => $user->id,
			'work_date' => $workDate->toDateString(),
			'clock_in_at' => Carbon::parse($workDate->toDateString() . ' 09:00:00'),
			'clock_out_at' => Carbon::parse($workDate->toDateString() . ' 18:00:00'),
			'break_minutes' => 0,
			'work_minutes' => 0,
			'note' => '初期備考',
			'status' => 'normal',
		]);
	}

	private function basePayload(Attendance $attendance): array
	{
		return [
			'attendance_id' => $attendance->id,
			'reason' => '修正申請',
			'before_attendance' => [
				'clock_in_at' => (string) $attendance->clock_in_at,
				'clock_out_at' => (string) $attendance->clock_out_at,
				'note' => (string) $attendance->note,
			],
			'after_attendance' => [
				'clock_in_at' => '09:00',
				'clock_out_at' => '18:00',
				'note' => '修正理由',
			],
			'before_breaks' => [],
			'after_breaks' => [],
		];
	}

	private function submitRequest(User $user, Attendance $attendance, array $overrides = []): void
	{
		$payload = array_replace_recursive($this->basePayload($attendance), $overrides);

		$this->actingAs($user)
			->from(route('attendance.detail', $attendance->id))
			->post(route('stamp_correction_request.store'), $payload)
			->assertRedirect(route('stamp_correction_request.list'));
	}

	/** 11. 勤怠詳細情報修正機能(一般ユーザー) */
	/** 11-1. 出勤時間が退勤時間より後になっている場合、エラーメッセージが表示される */
	public function test_clock_in_after_clock_out_shows_error_message(): void
	{
		$now = Carbon::create(2026, 1, 21, 10, 0, 0, config('app.timezone'));
		Carbon::setTestNow($now);

		$user = $this->makeUser();
		$attendance = $this->createAttendance($user, $now->copy());

		$payload = $this->basePayload($attendance);
		$payload['after_attendance']['clock_in_at'] = '19:00';
		$payload['after_attendance']['clock_out_at'] = '18:00';

		$this->actingAs($user)
			->from(route('attendance.detail', $attendance->id))
			->followingRedirects()
			->post(route('stamp_correction_request.store'), $payload)
			->assertOk()
			->assertSee('出勤時間もしくは退勤時間が不適切な値です');
	}

	/** 11-2. 休憩開始時間が退勤時間より後になっている場合、エラーメッセージが表示される */
	public function test_break_start_after_clock_out_shows_error_message(): void
	{
		$now = Carbon::create(2026, 1, 21, 10, 0, 0, config('app.timezone'));
		Carbon::setTestNow($now);

		$user = $this->makeUser();
		$attendance = $this->createAttendance($user, $now->copy());

		$payload = $this->basePayload($attendance);
		$payload['after_attendance']['clock_in_at'] = '09:00';
		$payload['after_attendance']['clock_out_at'] = '18:00';
		$payload['after_breaks'] = [
			['start_at' => '19:00', 'end_at' => '19:10'],
		];

		$this->actingAs($user)
			->from(route('attendance.detail', $attendance->id))
			->followingRedirects()
			->post(route('stamp_correction_request.store'), $payload)
			->assertOk()
			->assertSee('休憩時間が不適切な値です');
	}

	/** 11-3. 休憩終了時間が退勤時間より後になっている場合、エラーメッセージが表示される */
	public function test_break_end_after_clock_out_shows_error_message(): void
	{
		$now = Carbon::create(2026, 1, 21, 10, 0, 0, config('app.timezone'));
		Carbon::setTestNow($now);

		$user = $this->makeUser();
		$attendance = $this->createAttendance($user, $now->copy());

		$payload = $this->basePayload($attendance);
		$payload['after_attendance']['clock_in_at'] = '09:00';
		$payload['after_attendance']['clock_out_at'] = '18:00';
		$payload['after_breaks'] = [
			['start_at' => '17:30', 'end_at' => '18:10'],
		];

		$this->actingAs($user)
			->from(route('attendance.detail', $attendance->id))
			->followingRedirects()
			->post(route('stamp_correction_request.store'), $payload)
			->assertOk()
			->assertSee('休憩時間もしくは退勤時間が不適切な値です');
	}

	/** 11-4. 備考欄が未入力の場合のエラーメッセージが表示される */
	public function test_note_required_shows_error_message(): void
	{
		$now = Carbon::create(2026, 1, 21, 10, 0, 0, config('app.timezone'));
		Carbon::setTestNow($now);

		$user = $this->makeUser();
		$attendance = $this->createAttendance($user, $now->copy());

		$payload = $this->basePayload($attendance);
		$payload['after_attendance']['note'] = '';

		$this->actingAs($user)
			->from(route('attendance.detail', $attendance->id))
			->followingRedirects()
			->post(route('stamp_correction_request.store'), $payload)
			->assertOk()
			->assertSee('備考を記入してください');
	}

	/** 11-5. 修正申請処理が実行される */
	public function test_correction_request_is_created_and_visible_to_admin(): void
	{
		$now = Carbon::create(2026, 1, 21, 10, 0, 0, config('app.timezone'));
		Carbon::setTestNow($now);

		$user = $this->makeUser(['name' => '申請 太郎']);
		$attendance = $this->createAttendance($user, $now->copy());

		$this->submitRequest($user, $attendance, [
			'after_attendance' => [
				'clock_in_at' => '09:15',
				'clock_out_at' => '18:10',
				'note' => '理由1',
			],
		]);

		$changeRequest = AttendanceChangeRequest::query()->latest('id')->firstOrFail();

		$admin = $this->makeAdmin(['name' => '管理者 花子']);

		// 管理者の申請一覧に表示される
		$this->actingAs($admin)
			->withSession(['is_admin_login' => true])
			->get(route('stamp_correction_request.list'))
			->assertOk()
			->assertSee('申請一覧')
			->assertSee($user->name)
			->assertSee($now->format('Y/m/d'))
			->assertSee(route('stamp_correction_request.approve', ['attendance_change_request_id' => $changeRequest->id]));

		// 管理者の承認画面に表示される
		$this->actingAs($admin)
			->withSession(['is_admin_login' => true])
			->get(route('stamp_correction_request.approve', ['attendance_change_request_id' => $changeRequest->id]))
			->assertOk()
			->assertSee('勤怠詳細')
			->assertSee($user->name)
			->assertSee('09:15')
			->assertSee('18:10')
			->assertSee('理由1')
			->assertSee('承認');
	}

	/** 11-6. 「承認待ち」にログインユーザーが行った申請が全て表示されていること */
	public function test_pending_list_shows_all_own_requests(): void
	{
		$now = Carbon::create(2026, 1, 21, 10, 0, 0, config('app.timezone'));
		Carbon::setTestNow($now);

		$user = $this->makeUser(['name' => '申請 太郎']);

		$a1 = $this->createAttendance($user, Carbon::create(2026, 1, 20, 0, 0, 0, config('app.timezone')));
		$a2 = $this->createAttendance($user, Carbon::create(2026, 1, 21, 0, 0, 0, config('app.timezone')));

		$this->submitRequest($user, $a1, ['after_attendance' => ['note' => '理由1']]);
		$this->submitRequest($user, $a2, ['after_attendance' => ['note' => '理由2']]);

		$this->actingAs($user)
			->get(route('stamp_correction_request.list'))
			->assertOk()
			->assertSee('承認待ち')
			->assertSee('2026/01/20')
			->assertSee('理由1')
			->assertSee('2026/01/21')
			->assertSee('理由2');
	}

	/** 11-7. 「承認済み」に管理者が承認した修正申請が全て表示されている */
	public function test_approved_list_shows_admin_approved_requests(): void
	{
		$now = Carbon::create(2026, 1, 21, 10, 0, 0, config('app.timezone'));
		Carbon::setTestNow($now);

		$user = $this->makeUser(['name' => '申請 太郎']);
		$attendance = $this->createAttendance($user, $now->copy());

		$this->submitRequest($user, $attendance, ['after_attendance' => ['note' => '理由1']]);
		$changeRequest = AttendanceChangeRequest::query()->latest('id')->firstOrFail();

		$admin = $this->makeAdmin(['name' => '管理者 花子']);
		$this->actingAs($admin)
			->withSession(['is_admin_login' => true])
			->post(route('stamp_correction_request.approve.exec', ['attendance_change_request_id' => $changeRequest->id]))
			->assertRedirect(route('stamp_correction_request.approve', ['attendance_change_request_id' => $changeRequest->id]));

		$this->actingAs($user)
			->get(route('stamp_correction_request.list'))
			->assertOk()
			->assertSee('承認済み')
			->assertSee($now->format('Y/m/d'))
			->assertSee('理由1');
	}

	/** 11-8. 各申請の「詳細」を押下すると勤怠詳細画面に遷移する */
	public function test_request_detail_link_navigates_to_attendance_detail(): void
	{
		$now = Carbon::create(2026, 1, 21, 10, 0, 0, config('app.timezone'));
		Carbon::setTestNow($now);

		$user = $this->makeUser(['name' => '申請 太郎']);
		$attendance = $this->createAttendance($user, $now->copy());
		$this->submitRequest($user, $attendance, ['after_attendance' => ['note' => '理由1']]);

		$this->actingAs($user)
			->get(route('stamp_correction_request.list'))
			->assertOk()
			->assertSee(route('attendance.detail', $attendance->id));

		$this->actingAs($user)
			->get(route('attendance.detail', $attendance->id))
			->assertOk()
			->assertSee('勤怠詳細');
	}
}

