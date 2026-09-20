<?php

namespace App\Services\Discord;

use App\Domain\Client\Client;

/**
 * Wraps Discord's Guild Scheduled Events API. $payload/$changes are the
 * raw Discord fields (name, scheduled_start_time, entity_type, ...) -
 * see https://discord.com/developers/docs/resources/guild-scheduled-event.
 */
class EventService
{
    public function __construct(private readonly DiscordClient $discord) {}

    public function list(Client $client): array
    {
        return $this->discord->get("/guilds/{$client->discord_guild_id}/scheduled-events");
    }

    public function find(Client $client, string $discordEventId): array
    {
        return $this->discord->get("/guilds/{$client->discord_guild_id}/scheduled-events/{$discordEventId}");
    }

    public function create(Client $client, array $payload): array
    {
        return $this->discord->post("/guilds/{$client->discord_guild_id}/scheduled-events", $payload);
    }

    public function update(Client $client, string $discordEventId, array $changes): array
    {
        return $this->discord->patch("/guilds/{$client->discord_guild_id}/scheduled-events/{$discordEventId}", $changes);
    }

    public function delete(Client $client, string $discordEventId): void
    {
        $this->discord->delete("/guilds/{$client->discord_guild_id}/scheduled-events/{$discordEventId}");
    }
}
