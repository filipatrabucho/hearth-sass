<?php

namespace Database\Factories;

use App\Domain\Ticket\Ticket;
use App\Domain\Ticket\TicketMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TicketMessage>
 */
class TicketMessageFactory extends Factory
{
    protected $model = TicketMessage::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ticket_id' => Ticket::factory(),
            'discord_user_id' => (string) fake()->numerify('##################'),
            'body' => fake()->sentence(fake()->numberBetween(6, 20)),
        ];
    }
}
