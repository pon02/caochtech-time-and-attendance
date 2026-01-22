<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AdminAttendanceController;
use App\Http\Controllers\StampCorrectionRequestController;
use App\Http\Controllers\StaffController;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
Route::get('/admin/login', function () {
    return view('auth.admin_login');
})->name('admin.login');

// 管理者ログインPOSTルート追加
Route::post('/admin/login', [AuthenticatedSessionController::class, 'store'])->name('admin.login');

// 管理者ログアウトルートにwebミドルウェアを追加
Route::post('/admin/logout', function (Request $request) {
    auth()->logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    // 管理者ログアウト時にadminフラグも消す
    session()->forget('is_admin_login');
    return redirect('/admin/login');
})->middleware('web')->name('admin.logout');

// ========================================
// 認証必要ルート
// ========================================
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/attendance', [AttendanceController::class, 'show'])->name('attendance.time_record');
    Route::post('/attendance', [AttendanceController::class, 'stamp'])->name('attendance.stamp');

    Route::get('/attendance/list', [AttendanceController::class, 'index'])->name('attendance.index');

    Route::get('/attendance/detail/{attendance}', [AttendanceController::class, 'detail'])
        ->name('attendance.detail');

    Route::get('/stamp_correction_request/list', function(Request $request) {
        // role_id=1 かつ セッションadmin_loginフラグがtrueのときのみ管理者画面
        $user = $request->user();
        if ($user && (int)$user->role_id === 1 && session('is_admin_login') === true) {
            return app(AdminAttendanceController::class)->stampCorrectionRequestList($request);
        } else {
            return app(StampCorrectionRequestController::class)->index($request);
        }
    })->name('stamp_correction_request.list');
    Route::post('/stamp_correction_request', [StampCorrectionRequestController::class, 'store'])
        ->name('stamp_correction_request.store');
});

// 管理者権限必要ルート
Route::middleware(['auth','verified','admin'])->group(function () {
    Route::group(['middleware' => function ($request, $next) {
        if (session('is_admin_login') !== true) {
            abort(403, '管理者ログインが必要です');
        }
        return $next($request);
    }], function () {
        Route::get('/stamp_correction_request/approve/{attendance_change_request_id}',
        [StampCorrectionRequestController::class, 'approveForm'])
        ->name('stamp_correction_request.approve');
        Route::post('/stamp_correction_request/approve/{attendance_change_request_id}',
        [StampCorrectionRequestController::class, 'approve'])
        ->name('stamp_correction_request.approve.exec');
        Route::post('/stamp_correction_request/reject/{attendance_change_request_id}',
        [StampCorrectionRequestController::class, 'reject'])
        ->name('stamp_correction_request.reject');
    });
});
Route::prefix('admin')->middleware(['auth','verified','admin'])->group(function () {
    Route::group(['middleware' => function ($request, $next) {
        if (session('is_admin_login') !== true) {
            abort(403, '管理者ログインが必要です');
        }
        return $next($request);
    }], function () {
        Route::get('/attendance/list', [AdminAttendanceController::class, 'index'])
        ->name('admin.attendance.index');

        Route::get('/attendance/{attendance}', [AdminAttendanceController::class, 'show'])
            ->name('admin.attendance.show');
        Route::put('/attendance/{attendance}', [AdminAttendanceController::class, 'update'])
            ->name('admin.attendance.update');
        Route::get('/attendance/staff/{user}', [AdminAttendanceController::class, 'staffMonthly'])
            ->name('admin.attendance.staff');
        Route::get('/attendance/staff/{user}/csv', [AdminAttendanceController::class, 'exportCsv'])
            ->name('admin.attendance.staff.csv');
        Route::get('/staff/list', [StaffController::class, 'index'])
            ->name('admin.staff.index');
    });
});

// ========================================
// メール認証関連（Fortifyが自動処理）
// ========================================
// /email/verify - メール認証画面
// /email/verification-notification - 認証メール再送