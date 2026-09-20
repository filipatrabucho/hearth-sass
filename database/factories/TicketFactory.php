<?php

namespace Database\Factories;

use App\Domain\Client\Client;
use App\Domain\Ticket\Ticket;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ticket>
 */
class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'discord_user_id' => (string) fake()->numerify('##################'),
            'subject' => fake()->randomElement([
                'Não consigo aceder ao canal de eventos',
                'Pedido para trocar de rank',
                'Reportar comportamento de outro membro',
                'Dúvida sobre o Nitro boost',
                'Problema com o bot de música',
                'Pedido de unban',
            ]),
            'status' => fake()->randomElement(Ticket::STATUSES),
            'discord_channel_id' => (string) fake()->numerify('##################'),
        ];
    }
}
