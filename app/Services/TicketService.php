<?php

namespace App\Services;

use App\Domain\Client\Client;
use App\Domain\Ticket\Ticket;
use App\Domain\Ticket\TicketMessage;
use App\Domain\User\User;
use App\Services\Discord\ChannelService;
use App\Services\Discord\MessageService;

/**
 * Tickets are our own data (Discord has no concept of a "ticket"): each
 * one gets a private Discord text channel that mirrors the conversation,
 * so the member and staff can talk from Discord or from the HearthGG
 * dashboard interchangeably.
 */
class TicketService
{
    public function __construct(
        private readonly ChannelService $channels,
        private readonly MessageService $messages,
    ) {}

    public function open(Client $client, string $discordUserId, string $subject, ?string $firstMessage = null): Ticket
    {
        $channel = $this->channels->create($client, "ticket-{$discordUserId}", permissionOverwrites: [
            ['id' => $discordUserId, 'type' => 1, 'allow' => (string) (1 << 10)], // VIEW_CHANNEL
        ]);

        $ticket = Ticket::create([
            'client_id' => $client->id,
            'discord_user_id' => $discordUserId,
            'subject' => $subject,
            'status' => Ticket::STATUS_OPEN,
            'discord_channel_id' => $channel['id'],
        ]);

        if ($firstMessage) {
            $this->reply($ticket, discordUserId: $discordUserId, body: $firstMessage);
        }

        return $ticket;
    }

    public function reply(Ticket $ticket, ?User $staffUser = null, ?string $discordUserId = null, string $body = ''): TicketMessage
    {
        $message = TicketMessage::create([
            'ticket_id' => $ticket->id,
            'author_user_id' => $staffUser?->id,
            'discord_user_id' => $discordUserId,
            'body' => $body,
        ]);

        if ($ticket->discord_channel_id) {
            $author = $staffUser?->username ?? 'Membro';
            $this->messages->send($ticket->discord_channel_id, "**{$author}:** {$body}");
        }

        return $message;
    }

    public function updateStatus(Ticket $ticket, string $status): Ticket
    {
        $ticket->update(['status' => $status]);

        return $ticket->refresh();
    }

    public function close(Ticket $ticket): Ticket
    {
        if ($ticket->discord_channel_id) {
            $this->channels->delete($ticket->discord_channel_id, reason: 'Ticket closed');
        }

        return $this->updateStatus($ticket, Ticket::STATUS_CLOSED);
    }
}
