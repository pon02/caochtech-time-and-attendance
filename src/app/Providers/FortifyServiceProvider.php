<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Http\Requests\LoginRequest;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\Contracts\RegisterResponse;
use Laravel\Fortify\Contracts\VerifyEmailResponse;
use Laravel\Fortify\Contracts\LoginResponse;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        Fortify::registerView(function() {
            return view('auth.register');
        });

        // ログインビュー分岐
        Fortify::loginView(function() {
            if (request()->is('admin/login')) {
                return view('auth.admin_login');
            }
            return view('auth.login');
        });

        // 管理者ログインURLの場合はrole_id=1のみ許可、それ以外はrole_id問わず認証
        Fortify::authenticateUsing(function (Request $request) {
            try {
                $loginRequest = app(LoginRequest::class);
                $loginRequest->merge($request->all());
                $validated = $loginRequest->validated();
                $user = User::where('email', $validated['email'])->first();
                if ($user && \Illuminate\Support\Facades\Hash::check($validated['password'], $user->password)) {
                    if (request()->is('admin/login') && $user->role_id != 1) {
                        return null;
                    }
                    return $user;
                }
                return null;
            } catch (\Illuminate\Validation\ValidationException $e) {
                throw $e;
            }
        });

        $this->app->instance(LoginResponse::class, new class implements LoginResponse {
            public function toResponse($request)
            {
                $user = auth()->user();
                // 管理者ログイン画面からのログイン
                if (request()->is('admin/login')) {
                    if ($user && $user->role_id == 1) {
                        session(['is_admin_login' => true]);
                        return redirect('/admin/attendance/list');
                    }
                    // 管理者以外は一般ページへ
                    session()->forget('is_admin_login');
                    return redirect('/admin/login');
                }
                session()->forget('is_admin_login');
                return redirect('/attendance');
            }
        });

        Fortify::verifyEmailView(function () {
            return view('auth.verification');
        });

        $this->app->instance(RegisterResponse::class, new class implements RegisterResponse {
            public function toResponse($request)
            {
                return $request->wantsJson()
                    ? response()->json(['two_factor' => false])
                    : redirect()->route('verification.notice');
            }
        });

        $this->app->instance(VerifyEmailResponse::class, new class implements VerifyEmailResponse {
            public function toResponse($request)
            {
                session(['first_time_profile_setup' => true]);

                return $request->wantsJson()
                    ? response()->json(['status' => 'Email verified successfully'])
                    : redirect()->route('attendance.time_record')->with('status', 'メール認証が完了しました');
            }
        });

        RateLimiter::for('login', function(Request $request) {
            $email = (string) $request->email;
            return Limit::perMinute(5)->by($email . $request->ip());
        });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });
    }
}
