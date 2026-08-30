<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * The current passkey being used by the factory.
     */
    protected static ?string $passkey;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'username' => fake()->unique()->userName(),
            'name' => fake()->name(),
            'password' => static::$password ??= Hash::make('admin123'),
            'passkey' => null,
            'role' => User::ROLE_OPERATOR,
        ];
    }

    /**
     * Give the user the admin role.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => User::ROLE_ADMIN,
        ]);
    }

    /**
     * Assign a fixed six-digit passkey to the user.
     */
    public function withPasskey(string $passkey = '123456'): static
    {
        return $this->state(fn (array $attributes) => [
            'passkey' => static::$passkey ??= Hash::make($passkey),
        ]);
    }
}
