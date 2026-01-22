<?php

namespace Tests\Feature\Admin;

use App\Models\Attendance;
use App\Models\AttendanceBreak;
use App\Models\AttendanceChangeRequest;
use App\Models\AttendanceChangeRequestPayload;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AdminStampCorrectionRequestTest extends TestCase
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

	private function createAttendance(User $user, Carbon $workDate, array $overrides = []): Attendance
	{
		return Attendance::create(array_merge([
			'user_id' => $user->id,
			'work_date' => $workDate->toDateString(),
			'clock_in_at' => Carbon::parse($workDate->toDateString() . ' 09:00:00'),
			'clock_out_at' => Carbon::parse($workDate->toDateString() . ' 18:00:00'),
			'break_minutes' => 60,
			'work_minutes' => 480,
			'note' => null,
			'status' => 'normal',
		], $overrides));
	}

	private function createBreak(Attendance $attendance, Carbon $workDate, string $start, string $end): AttendanceBreak
	{
		return AttendanceBreak::create([
			'attendance_id' => $attendance->id,
			'start_at' => Carbon::parse($workDate->toDateString() . ' ' . $start . ':00'),
			'end_at' => Carbon::parse($workDate->toDateString() . ' ' . $end . ':00'),
		]);
	}

	private function createChangeRequestWithPayload(
		Attendance $attendance,
		User $requester,
		string $status,
		array $afterAttendance,
		array $afterBreaks
	): AttendanceChangeRequest {
		$request = AttendanceChangeRequest::create([
			'attendance_id' => $attendance->id,
			'requester_user_id' => $requester->id,
			'status' => $status,
			'reviewer_user_id' => null,
			'reviewed_at' => null,
		]);

		AttendanceChangeRequestPayload::create([
			'attendance_change_request_id' => $request->id,
			'before_attendance' => [
				'clock_in_at' => $attendance->clock_in_at,
				'clock_out_at' => $attendance->clock_out_at,
				'note' => $attendance->note,
			],
			'after_attendance' => $afterAttendance,
			'before_breaks' => [],
			'after_breaks' => $afterBreaks,
		]);

		return $request;
	}

	/** 15. 勤怠情報修正機能(管理者) */
	/** 15-1. 承認待ちの修正申請が全て表示されている */
	public function test_admin_can_see_all_pending_change_requests(): void
	{
		$now = Carbon::create(2026, 1, 21, 10, 0, 0, config('app.timezone'));
		Carbon::setTestNow($now);

		$admin = $this->makeAdmin();
		$u1 = $this->makeUser(['name' => '申請者A']);
		$u2 = $this->makeUser(['name' => '申請者B']);

		$a1 = $this->createAttendance($u1, Carbon::create(2026, 1, 10, 0, 0, 0, config('app.timezone')));
		$a2 = $this->createAttendance($u2, Carbon::create(2026, 1, 11, 0, 0, 0, config('app.timezone')));

		$r1 = $this->createChangeRequestWithPayload(
			attendance: $a1,
			requester: $u1,
			status: 'pending',
			afterAttendance: ['clock_in_at' => '09:05', 'clock_out_at' => '18:05', 'note' => '理由A'],
			afterBreaks: []
		);
		$r2 = $this->createChangeRequestWithPayload(
			attendance: $a2,
			requester: $u2,
			status: 'pending',
			afterAttendance: ['clock_in_at' => '08:55', 'clock_out_at' => '17:55', 'note' => '理由B'],
			afterBreaks: []
		);

		$response = $this->actingAs($admin)
			->withSession(['is_admin_login' => true])
			->get(route('stamp_correction_request.list'));

		$response->assertOk();
		$response
			->assertSee('申請一覧')
			->assertSee('承認待ち')
			->assertSee($u1->name)
			->assertSee('2026/01/10')
			->assertSee('理由A')
			->assertSee(route('stamp_correction_request.approve', ['attendance_change_request_id' => $r1->id]))
			->assertSee($u2->name)
			->assertSee('2026/01/11')
			->assertSee('理由B')
			->assertSee(route('stamp_correction_request.approve', ['attendance_change_request_id' => $r2->id]));
	}

	/** 15-2. 承認済みの修正申請が全て表示されている */
	public function test_admin_can_see_all_approved_change_requests(): void
	{
		$now = Carbon::create(2026, 1, 21, 10, 0, 0, config('app.timezone'));
		Carbon::setTestNow($now);

		$admin = $this->makeAdmin();
		$u1 = $this->makeUser(['name' => '承認済み申請者']);

		$workDate = Carbon::create(2026, 1, 5, 0, 0, 0, config('app.timezone'));
		$attendance = $this->createAttendance($u1, $workDate);

		$this->createChangeRequestWithPayload(
			attendance: $attendance,
			requester: $u1,
			status: 'approved',
			afterAttendance: ['clock_in_at' => '09:10', 'clock_out_at' => '18:10', 'note' => '理由C'],
			afterBreaks: []
		);

		$this->actingAs($admin)
			->withSession(['is_admin_login' => true])
			->get(route('stamp_correction_request.list'))
			->assertOk()
			->assertSee('承認済み')
			->assertSee($u1->name)
			->assertSee('2026/01/05')
			->assertSee('理由C')
			->assertSee(route('admin.attendance.show', ['attendance' => $attendance->id]));
	}

	/** 15-3. 修正申請の詳細内容が正しく表示されている */
	public function test_admin_can_view_change_request_detail(): void
	{
		$now = Carbon::create(2026, 1, 21, 10, 0, 0, config('app.timezone'));
		Carbon::setTestNow($now);

		$admin = $this->makeAdmin();
		$user = $this->makeUser(['name' => '詳細申請者']);
		$workDate = Carbon::create(2026, 1, 21, 0, 0, 0, config('app.timezone'));
		$attendance = $this->createAttendance($user, $workDate);
		$break = $this->createBreak($attendance, $workDate, '12:00', '13:00');

		$request = $this->createChangeRequestWithPayload(
			attendance: $attendance,
			requester: $user,
			status: 'pending',
			afterAttendance: [
				'clock_in_at' => '2026-01-21 09:10:00',
				'clock_out_at' => '2026-01-21 18:05:00',
				'note' => '詳細理由',
			],
			afterBreaks: [
				['id' => $break->id, 'start_at' => '2026-01-21 12:10:00', 'end_at' => '2026-01-21 12:40:00'],
			]
		);

		$this->actingAs($admin)
			->withSession(['is_admin_login' => true])
			->get(route('stamp_correction_request.approve', ['attendance_change_request_id' => $request->id]))
			->assertOk()
			->assertSee('勤怠詳細')
			->assertSee($user->name)
			->assertSee('2026年')
			->assertSee('1月21日')
			->assertSee('09:10')
			->assertSee('18:05')
			->assertSee('休憩1')
			->assertSee('12:10')
			->assertSee('12:40')
			->assertSee('備考')
			->assertSee('詳細理由')
			->assertSee('承認');
	}

	/** 15-4. 修正申請の承認処理が正しく行われる */
	public function test_admin_can_approve_change_request_and_attendance_is_updated(): void
	{
		$now = Carbon::create(2026, 1, 21, 10, 0, 0, config('app.timezone'));
		Carbon::setTestNow($now);

		$admin = $this->makeAdmin();
		$user = $this->makeUser(['name' => '承認対象ユーザー']);
		$workDate = Carbon::create(2026, 1, 20, 0, 0, 0, config('app.timezone'));
		$attendance = $this->createAttendance($user, $workDate, [
			'clock_in_at' => Carbon::parse('2026-01-20 09:00:00'),
			'clock_out_at' => Carbon::parse('2026-01-20 18:00:00'),
			'break_minutes' => 60,
			'work_minutes' => 480,
		]);
		$break = $this->createBreak($attendance, $workDate, '12:00', '13:00');

		$request = $this->createChangeRequestWithPayload(
			attendance: $attendance,
			requester: $user,
			status: 'pending',
			afterAttendance: [
				'clock_in_at' => '09:10',
				'clock_out_at' => '18:05',
				'note' => '承認反映',
			],
			afterBreaks: [
				['id' => $break->id, 'start_at' => '12:10', 'end_at' => '12:40'],
			]
		);

		$this->actingAs($admin)
			->withSession(['is_admin_login' => true])
			->post(route('stamp_correction_request.approve.exec', ['attendance_change_request_id' => $request->id]), [])
			->assertRedirect(route('stamp_correction_request.approve', ['attendance_change_request_id' => $request->id]));

		$attendance->refresh();
		$request->refresh();
		$break->refresh();

		$this->assertSame('approved', $request->status);
		$this->assertSame($admin->id, $request->reviewer_user_id);
		$this->assertNotNull($request->reviewed_at);

		$this->assertSame('2026-01-20 09:10:00', Carbon::parse($attendance->clock_in_at)->toDateTimeString());
		$this->assertSame('2026-01-20 18:05:00', Carbon::parse($attendance->clock_out_at)->toDateTimeString());
		$this->assertSame('承認反映', $attendance->note);

		$this->assertSame('2026-01-20 12:10:00', Carbon::parse($break->start_at)->toDateTimeString());
		$this->assertSame('2026-01-20 12:40:00', Carbon::parse($break->end_at)->toDateTimeString());
	}
}
