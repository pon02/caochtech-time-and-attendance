<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class StaffController extends Controller
{
    public function index(Request $request)
    {
        // 管理者自身も一般ユーザーとして使える要件なので、ここでは全ユーザーを対象にしています。
        // 「管理者は除外したい」なら ->where('role', 'user') を付けてください。
        $staffs = User::query()
            ->select(['id', 'name', 'email'])
            ->orderBy('name')
            ->paginate(50);

        return view('admin.staff', [
            'staffs' => $staffs,
        ]);
    }
}
