<?php

namespace Tests\Feature\Admin;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AdminAttendanceListTest extends TestCase
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

	/** 12. 勤怠一覧情報取得機能(管理者) */
	/** 12-1. その日になされた全ユーザーの勤怠情報が正確に確認できる */
	public function test_admin_can_see_all_users_attendance_for_the_day(): void
	{
		$now = Carbon::create(2026, 1, 21, 10, 0, 0, config('app.timezone'));
		Carbon::setTestNow($now);

		$admin = $this->makeAdmin();
		$u1 = $this->makeUser(['name' => '佐藤 一郎']);
		$u2 = $this->makeUser(['name' => '鈴木 次郎']);

		$a1 = $this->createAttendance($u1, $now->copy(), '09:10', '18:05', 30, 505);
		$a2 = $this->createAttendance($u2, $now->copy(), '08:55', '17:40', 45, 520);

		$response = $this->actingAs($admin)
			->withSession(['is_admin_login' => true])
			->get(route('admin.attendance.index'));

		$response->assertOk();
		$response
			->assertSee($u1->name)
			->assertSee('09:10')
			->assertSee('18:05')
			->assertSee('00:30')
			->assertSee('08:25')
			->assertSee(route('admin.attendance.show', $a1->id));

		$response
			->assertSee($u2->name)
			->assertSee('08:55')
			->assertSee('17:40')
			->assertSee('00:45')
			->assertSee('08:40')
			->assertSee(route('admin.attendance.show', $a2->id));
	}

	/** 12-2. 遷移した際に現在の日付が表示される */
	public function test_admin_attendance_list_shows_current_date_on_open(): void
	{
		$now = Carbon::create(2026, 1, 21, 10, 0, 0, config('app.timezone'));
		Carbon::setTestNow($now);

		$admin = $this->makeAdmin();

		$this->actingAs($admin)
			->withSession(['is_admin_login' => true])
			->get(route('admin.attendance.index'))
			->assertOk()
			->assertSee($now->format('Y年m月d日') . 'の勤怠')
			->assertSee($now->format('Y/m/d'));
	}

	/** 12-3. 「前日」を押下した時に前の日の勤怠情報が表示される */
	public function test_admin_can_view_prev_day_attendance(): void
	{
		$now = Carbon::create(2026, 1, 21, 10, 0, 0, config('app.timezone'));
		Carbon::setTestNow($now);
		$prev = $now->copy()->subDay();

		$admin = $this->makeAdmin();
		$user = $this->makeUser(['name' => '前日 ユーザー']);
		$this->createAttendance($user, $prev->copy(), '09:00', '18:00', 60, 480);

		// ボタンのリンクが前日を向いている
		$this->actingAs($admin)
			->withSession(['is_admin_login' => true])
			->get(route('admin.attendance.index'))
			->assertOk()
			->assertSee('?date=' . $prev->toDateString());

		// 前日ページで前日のデータが見える
		$this->actingAs($admin)
			->withSession(['is_admin_login' => true])
			->get(route('admin.attendance.index', ['date' => $prev->toDateString()]))
			->assertOk()
			->assertSee($prev->format('Y年m月d日') . 'の勤怠')
			->assertSee($user->name)
			->assertSee('09:00')
			->assertSee('18:00')
			->assertSee('01:00')
			->assertSee('08:00');
	}

	/** 12-4. 「翌日」を押下した時に次の日の勤怠情報が表示される */
	public function test_admin_can_view_next_day_attendance(): void
	{
		$now = Carbon::create(2026, 1, 21, 10, 0, 0, config('app.timezone'));
		Carbon::setTestNow($now);
		$next = $now->copy()->addDay();

		$admin = $this->makeAdmin();
		$user = $this->makeUser(['name' => '翌日 ユーザー']);
		$this->createAttendance($user, $next->copy(), '10:00', '19:00', 0, 540);

		// ボタンのリンクが翌日を向いている
		$this->actingAs($admin)
			->withSession(['is_admin_login' => true])
			->get(route('admin.attendance.index'))
			->assertOk()
			->assertSee('?date=' . $next->toDateString());

		// 翌日ページで翌日のデータが見える
		$this->actingAs($admin)
			->withSession(['is_admin_login' => true])
			->get(route('admin.attendance.index', ['date' => $next->toDateString()]))
			->assertOk()
			->assertSee($next->format('Y年m月d日') . 'の勤怠')
			->assertSee($user->name)
			->assertSee('10:00')
			->assertSee('19:00')
			->assertSee('00:00')
			->assertSee('09:00');
	}
}
