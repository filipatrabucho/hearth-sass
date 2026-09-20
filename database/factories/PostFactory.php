<?php

namespace Database\Factories;

use App\Domain\Client\Client;
use App\Domain\Post\Post;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    protected $model = Post::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'discord_channel_id' => (string) fake()->numerify('##################'),
            'title' => fake()->randomElement([
                'Novo evento este fim de semana!',
                'Atualização das regras do servidor',
                'Manutenção agendada',
                'Bem-vindos aos novos membros',
                'Resultados do torneio de sábado',
            ]),
            'content' => fake()->paragraphs(fake()->numberBetween(1, 3), true),
            'status' => Post::STATUS_DRAFT,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Post::STATUS_PUBLISHED,
            'discord_message_id' => (string) fake()->numerify('##################'),
            'published_at' => fake()->dateTimeBetween('-3 weeks', 'now'),
        ]);
    }
}
