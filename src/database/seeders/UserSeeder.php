<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // 固定 管理者
        User::create([
            'name' => '管理者 太郎',
            'email' => 'admin@example.com',
            'password' => Hash::make('09876543'),
            'role_id' => 1,
            'email_verified_at' => now(),
        ]);

        // 固定 一般ユーザー
        User::create([
            'name' => '一般 花子',
            'email' => 'user@example.com',
            'password' => Hash::make('12345678'),
            'role_id' => 2,
            'email_verified_at' => now(),
        ]);

        User::factory()->count(1)->admin()->create();

        User::factory()->count(17)->create();
    }
}