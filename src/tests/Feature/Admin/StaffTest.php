<?php

namespace Tests\Feature\Admin;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class StaffTest extends TestCase
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

	private function jpWeekday(Carbon $date): string
	{
		return ['日', '月', '火', '水', '木', '金', '土'][$date->dayOfWeek];
	}

	/** 14. ユーザー情報取得機能(管理者) */
	/** 14-1. 管理者ユーザーが全一般ユーザーの「氏名」「メールアドレス」を確認できる */
	public function test_admin_can_see_all_staff_name_and_email(): void
	{
		$now = Carbon::create(2026, 1, 21, 10, 0, 0, config('app.timezone'));
		Carbon::setTestNow($now);

		$admin = $this->makeAdmin();
		$u1 = $this->makeUser(['name' => '一般 ユーザーA', 'email' => 'usera@example.test']);
		$u2 = $this->makeUser(['name' => '一般 ユーザーB', 'email' => 'userb@example.test']);

		$this->actingAs($admin)
			->withSession(['is_admin_login' => true])
			->get(route('admin.staff.index'))
			->assertOk()
			->assertSee('スタッフ一覧')
			->assertSee($u1->name)
			->assertSee($u1->email)
			->assertSee($u2->name)
			->assertSee($u2->email);
	}

	/** 14-2. ユーザーの勤怠情報が正しく表示される */
	public function test_admin_can_view_selected_user_monthly_attendance(): void
	{
		$now = Carbon::create(2026, 1, 21, 10, 0, 0, config('app.timezone'));
		Carbon::setTestNow($now);

		$admin = $this->makeAdmin();
		$user = $this->makeUser(['name' => '月次 表示ユーザー']);
		$date = Carbon::create(2026, 1, 21, 0, 0, 0, config('app.timezone'));

		$this->createAttendance($user, $date, '07:01', '16:02', 13, 528);
		$expectedDate = $date->format('m/d') . '(' . $this->jpWeekday($date) . ')';

		$this->actingAs($admin)
			->withSession(['is_admin_login' => true])
			->get(route('admin.attendance.staff', ['user' => $user->id, 'month' => '2026-01']))
			->assertOk()
			->assertSee($user->name . 'さんの勤怠')
			->assertSee('2026年01月')
			->assertSee($expectedDate)
			->assertSee('07:01')
			->assertSee('16:02')
			->assertSee('00:13')
			->assertSee('08:48');
	}

	/** 14-3. 「前月」を押下した時に表示月の前月の情報が表示される */
	public function test_admin_can_view_prev_month_attendance(): void
	{
		$now = Carbon::create(2026, 1, 21, 10, 0, 0, config('app.timezone'));
		Carbon::setTestNow($now);

		$admin = $this->makeAdmin();
		$user = $this->makeUser(['name' => '前月 表示ユーザー']);

		$decDate = Carbon::create(2025, 12, 15, 0, 0, 0, config('app.timezone'));
		$this->createAttendance($user, $decDate, '06:11', '15:22', 7, 544);
		$expectedDate = $decDate->format('m/d') . '(' . $this->jpWeekday($decDate) . ')';

		// 前月ページで前月の情報が見える
		$this->actingAs($admin)
			->withSession(['is_admin_login' => true])
			->get(route('admin.attendance.staff', ['user' => $user->id, 'month' => '2025-12']))
			->assertOk()
			->assertSee('2025年12月')
			->assertSee($expectedDate)
			->assertSee('06:11')
			->assertSee('15:22')
			->assertSee('00:07')
			->assertSee('09:04');
	}

	/** 14-4. 「翌月」を押下した時に表示月の翌月の情報が表示されている */
	public function test_admin_can_view_next_month_attendance(): void
	{
		$now = Carbon::create(2026, 1, 21, 10, 0, 0, config('app.timezone'));
		Carbon::setTestNow($now);

		$admin = $this->makeAdmin();
		$user = $this->makeUser(['name' => '翌月 表示ユーザー']);

		$febDate = Carbon::create(2026, 2, 3, 0, 0, 0, config('app.timezone'));
		$this->createAttendance($user, $febDate, '05:33', '14:44', 11, 540);
		$expectedDate = $febDate->format('m/d') . '(' . $this->jpWeekday($febDate) . ')';

		// 翌月ページで翌月の情報が見える
		$this->actingAs($admin)
			->withSession(['is_admin_login' => true])
			->get(route('admin.attendance.staff', ['user' => $user->id, 'month' => '2026-02']))
			->assertOk()
			->assertSee('2026年02月')
			->assertSee($expectedDate)
			->assertSee('05:33')
			->assertSee('14:44')
			->assertSee('00:11')
			->assertSee('09:00');
	}

	/** 14-5. 「詳細」を押下すると、その日の勤怠詳細画面に遷移する */
	public function test_admin_can_navigate_to_attendance_detail_from_monthly_list(): void
	{
		$now = Carbon::create(2026, 1, 21, 10, 0, 0, config('app.timezone'));
		Carbon::setTestNow($now);

		$admin = $this->makeAdmin();
		$user = $this->makeUser(['name' => '詳細 遷移ユーザー']);
		$date = Carbon::create(2026, 1, 8, 0, 0, 0, config('app.timezone'));
		$attendance = $this->createAttendance($user, $date, '04:56', '13:21', 9, 496);

		$this->actingAs($admin)
			->withSession(['is_admin_login' => true])
			->get(route('admin.attendance.staff', ['user' => $user->id, 'month' => '2026-01']))
			->assertOk()
			->assertSee(route('admin.attendance.show', $attendance->id));

		$this->actingAs($admin)
			->withSession(['is_admin_login' => true])
			->get(route('admin.attendance.show', $attendance->id))
			->assertOk()
			->assertSee('勤怠詳細');
	}
}

