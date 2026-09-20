<?php

namespace Database\Factories;

use App\Domain\User\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'discord_id' => (string) fake()->unique()->numerify('##################'),
            'username' => fake()->unique()->userName(),
            'discriminator' => '0',
            'global_name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'avatar_hash' => null,
            'is_super_admin' => false,
            'last_login_at' => now(),
        ];
    }

    public function superAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_super_admin' => true,
        ]);
    }
}
