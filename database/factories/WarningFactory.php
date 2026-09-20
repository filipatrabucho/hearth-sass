<?php

namespace Database\Factories;

use App\Domain\Client\Client;
use App\Domain\Warning\Warning;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Warning>
 */
class WarningFactory extends Factory
{
    protected $model = Warning::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'discord_user_id' => (string) fake()->numerify('##################'),
            'reason' => fake()->randomElement([
                'Spam em vários canais.',
                'Linguagem ofensiva contra outro membro.',
                'Publicidade não autorizada.',
                'Desrespeito pelas regras de conduta.',
                'Flood de mensagens.',
            ]),
        ];
    }

    public function resolved(): static
    {
        return $this->state(fn (array $attributes) => [
            'resolved_at' => fake()->dateTimeBetween('-2 weeks', 'now'),
        ]);
    }
}
