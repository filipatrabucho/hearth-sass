<?php

namespace Database\Factories;

use App\Domain\Lead\Lead;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lead>
 */
class LeadFactory extends Factory
{
    protected $model = Lead::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'discord_username' => fake()->userName(),
            'server_name' => fake()->company(),
            'plan_interest' => fake()->randomElement(['free', 'pro', 'enterprise']),
            'message' => fake()->optional()->sentence(),
            'status' => Lead::STATUS_NEW,
            'source' => 'homepage',
        ];
    }
}
