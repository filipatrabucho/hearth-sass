<?php

namespace Database\Factories;

use App\Domain\Client\Client;
use App\Domain\User\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
{
    protected $model = Client::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'discord_guild_id' => (string) fake()->unique()->numerify('##################'),
            'name' => fake()->company(),
            'owner_user_id' => User::factory(),
            'plan' => 'free',
            'status' => 'active',
        ];
    }
}
