<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use App\Models\User;
use Carbon\Carbon;
use Tests\TestCase;

class VerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /** 16. メール認証機能 */
    /** 16-1. 会員登録後、認証メールが送信される */
    public function test_verifying_email(): void
    {
        Notification::fake();

        $this->get('/register')->assertOk();

        $this->post('/register', [
            'name' => 'shimura',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect('/email/verify');

        $user = User::where('email', 'test@example.com')->first();

        $this->assertDatabaseHas('users', ['name' => 'shimura', 'email' => 'test@example.com']);

        Notification::assertSentTo($user, \Illuminate\Auth\Notifications\VerifyEmail::class);
    }

    /** 16-2. メール認証誘導画面で「認証はこちらから」ボタンを押下するとメール認証サイトに遷移する */
    public function test_navigate_to_mailhog(): void
    {
        $user = User::factory()->create(['email_verified_at' => null]);

        $this->actingAs($user)
             ->get('/email/verify')
             ->assertOk()
             ->assertSee('認証はこちらから')
             ->assertSee('http://localhost:8025');

        try {
            $mailhogResponse = Http::timeout(3)->get('http://mailhog:8025');
            $this->assertTrue($mailhogResponse->successful(), 'MailHogサービスにアクセスできません');
        } catch (\Exception $e) {
            $this->markTestSkipped('MailHogサービスが利用できません: ' . $e->getMessage());
        }
    }

    /** 16-3. メール認証サイトのメール認証を完了すると、勤怠登録画面に遷移する */
    public function test_redirects_to_profile(): void
    {
        $user = User::factory()->create(['email_verified_at' => null]);

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $this->actingAs($user)
             ->get($verificationUrl)
             ->assertRedirect(route('attendance.time_record'));

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }
}
