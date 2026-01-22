<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $now = now();

        DB::table('roles')->updateOrInsert(
            ['id' => 1],
            ['name' => 'admin', 'created_at' => $now, 'updated_at' => $now]
        );

        DB::table('roles')->updateOrInsert(
            ['id' => 2],
            ['name' => 'user', 'created_at' => $now, 'updated_at' => $now]
        );
    }
}