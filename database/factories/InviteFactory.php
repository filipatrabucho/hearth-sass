<?php

namespace Database\Factories;

use App\Domain\Client\Client;
use App\Domain\Invite\Invite;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invite>
 */
class InviteFactory extends Factory
{
    protected $model = Invite::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'code' => fake()->unique()->regexify('[a-zA-Z0-9]{7}'),
            'inviter_discord_id' => (string) fake()->numerify('##################'),
            'uses' => fake()->numberBetween(0, 200),
            'max_uses' => fake()->randomElement([null, 0, 25, 50, 100]),
            'expires_at' => fake()->optional()->dateTimeBetween('now', '+30 days'),
            'synced_at' => now(),
        ];
    }
}
