<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected $model = User::class;

    protected static ?string $fixedPasswordHash = null;
    protected static ?string $fixedAdminPasswordHash = null;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'password' => static::$fixedPasswordHash ??= Hash::make('12345678'),
            'role_id' => 2,
            'remember_token' => Str::random(10),
            'email_verified_at' => now(),
        ];
    }

    public function admin(): static
    {
        return $this->state(fn () => [
            'role_id' => 1,
            'password' => static::$fixedAdminPasswordHash ??= Hash::make('09876543'),
            'email_verified_at' => now(),
        ]);
    }
}
