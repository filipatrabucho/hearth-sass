<?php

namespace App\Services\Discord;

/**
 * Sends/edits/deletes messages in a channel (used by PostService and
 * TicketService for their Discord side) and direct messages to a member
 * (used by WarningService).
 */
class MessageService
{
    public function __construct(private readonly DiscordClient $discord) {}

    public function send(string $discordChannelId, string $content): array
    {
        return $this->discord->post("/channels/{$discordChannelId}/messages", ['content' => $content]);
    }

    public function edit(string $discordChannelId, string $discordMessageId, string $content): array
    {
        return $this->discord->patch("/channels/{$discordChannelId}/messages/{$discordMessageId}", ['content' => $content]);
    }

    public function delete(string $discordChannelId, string $discordMessageId): void
    {
        $this->discord->delete("/channels/{$discordChannelId}/messages/{$discordMessageId}");
    }

    public function sendDirect(string $discordUserId, string $content): array
    {
        $dmChannel = $this->discord->post('/users/@me/channels', ['recipient_id' => $discordUserId]);

        return $this->send($dmChannel['id'], $content);
    }
}
