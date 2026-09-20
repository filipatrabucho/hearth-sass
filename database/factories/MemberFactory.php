<?php

namespace Database\Factories;

use App\Domain\Client\Client;
use App\Domain\Member\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Member>
 */
class MemberFactory extends Factory
{
    protected $model = Member::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'discord_user_id' => (string) fake()->unique()->numerify('##################'),
            'username' => fake()->unique()->userName(),
            'role_ids' => [],
            'synced_at' => now(),
        ];
    }
}
