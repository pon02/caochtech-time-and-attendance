<?php

namespace App\Actions\Fortify;

use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        // 管理者ログイン画面からのログイン時のみadminフラグをセット
        if ($request->is('admin/login')) {
            session(['is_admin_login' => true]);
        } else {
            session()->forget('is_admin_login');
        }
        return redirect()->intended(config('fortify.home'));
    }
}
