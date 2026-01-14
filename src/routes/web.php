<?php

use Illuminate\Support\Facades\Route;

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

// ========================================
// 認証必要ルート
// ========================================


// ========================================
// メール認証関連（Fortifyが自動処理）
// ========================================
// /email/verify - メール認証画面
// /email/verification-notification - 認証メール再送