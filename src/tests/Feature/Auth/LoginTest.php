<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    private function submitLogin($data = [])
    {
        $defaults = [
            'email' => 'test@example.com',
            'password' => 'password123',
        ];

        return $this->post('/login', array_merge($defaults, $data));
    }

    /** 2.ログイン機能(一般ユーザー) & 3.ログイン機能(管理者) */
    /** 2-1. メールアドレスが入力されていない場合、バリデーションメッセージが表示される */
    public function test_login_email_validation(): void
    {
        $this->get('/login')->assertOk();

        $this->submitLogin(['email' => ''])
             ->assertSessionHasErrors(['email' => 'メールアドレスを入力してください']);
    }

    /** 2-2. パスワードが入力されていない場合、バリデーションメッセージが表示される */
    public function test_login_password_validation(): void
    {
        $this->get('/login')->assertOk();

        $this->submitLogin(['password' => ''])
             ->assertSessionHasErrors(['password' => 'パスワードを入力してください']);
    }

    /** 2-3. 入力情報が間違っている場合、バリデーションメッセージが表示される */
    public function test_login_invalid_credentials(): void
    {
        $this->get('/login')->assertOk();

        $this->submitLogin(['email' => 'invalid@example.com', 'password' => 'wrongpassword'])
             ->assertSessionHasErrors(['email' => 'ログイン情報が登録されていません']);
    }

    /** 2-4. 正しい情報が入力された場合、ログイン処理が実行される */
    public function test_login_successful(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        $this->submitLogin()
             ->assertRedirect('/attendance');

        $this->assertAuthenticatedAs($user);
    }

    /** ログアウトができる */
    public function test_logout(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
             ->post('/logout')
               ->assertRedirect('/login');

        $this->assertGuest();
    }

    /** 3-1.管理者ログインでメールアドレスが未入力の場合、バリデーションメッセージが表示される */
    public function test_admin_login_email_validation(): void
    {
        $this->get('/admin/login')->assertOk();

        $this->post('/admin/login', [
            'email' => '',
            'password' => 'password123',
        ])->assertSessionHasErrors(['email' => 'メールアドレスを入力してください']);
    }

    /** 3-2.管理者ログインでパスワードが未入力の場合、バリデーションメッセージが表示される */
    public function test_admin_login_password_validation(): void
    {
        $this->get('/admin/login')->assertOk();
        $this->post('/admin/login', [
            'email' => '',
            'password' => '',
        ])->assertSessionHasErrors(['password' => 'パスワードを入力してください']);
    }

    /** 3-3.管理者ログインで入力情報が間違っている場合、バリデーションメッセージが表示される */
    public function test_admin_login_invalid_credentials(): void
    {
        $this->get('/admin/login')->assertOk();
        $this->post('/admin/login', [
            'email' => 'invalid@example.com',
            'password' => 'wrongpassword',
        ])->assertSessionHasErrors(['email' => 'ログイン情報が登録されていません']);
    }
}
