<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    private function submitRegistration($data = [])
    {
        $defaults = [
            'name' => 'shimura',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        return $this->post('/register', array_merge($defaults, $data));
    }

    /** 1. 認証機能(一般ユーザー) */
    /** 1-1. 名前が入力されていない場合、バリデーションメッセージが表示される */
    public function test_register_name_validation(): void
    {
        $this->get('/register')->assertOk();

        $this->submitRegistration(['name' => ''])
             ->assertSessionHasErrors(['name' => 'お名前を入力してください']);
    }

    /** 1-2. メールアドレスが入力されていない場合、バリデーションメッセージが表示される */
    public function test_register_email_validation(): void
    {
        $this->get('/register')->assertOk();

        $this->submitRegistration(['email' => ''])
             ->assertSessionHasErrors(['email' => 'メールアドレスを入力してください']);
    }

    /** 1-3. パスワードが入力されていない場合、バリデーションメッセージが表示される */
    public function test_register_password_validation(): void
    {
        $this->get('/register')->assertOk();

        $this->submitRegistration(['password' => ''])
             ->assertSessionHasErrors(['password' => 'パスワードを入力してください']);
    }

    /** 1-4. パスワードが7文字以下の場合、バリデーションメッセージが表示される */
    public function test_register_password_length_validation(): void
    {
        $this->get('/register')->assertOk();

        $this->submitRegistration(['password' => 'short', 'password_confirmation' => 'short'])
             ->assertSessionHasErrors(['password' => 'パスワードは8文字以上で入力してください']);
    }

    /** 1-5. パスワードが確認用パスワードと一致しない場合、バリデーションメッセージが表示される */
    public function test_register_password_mismatch_validation(): void
    {
        $this->get('/register')->assertOk();

        $this->submitRegistration(['password_confirmation' => 'different123'])
             ->assertSessionHasErrors(['password_confirmation' => 'パスワードと一致しません']);
    }

    /** 1-6. 全ての項目が入力されている場合、会員情報が登録され、メール確認画面に遷移する */
    public function test_creates_user(): void
    {
        $this->get('/register')->assertOk();

        $this->submitRegistration()
             ->assertRedirect('/email/verify');

        $this->assertDatabaseHas('users', ['name' => 'shimura', 'email' => 'test@example.com'])
             ->assertAuthenticated();
    }
}
