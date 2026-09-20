<?php

namespace App\Services\Discord;

use App\Domain\Client\Client;

class ChannelService
{
    public function __construct(private readonly DiscordClient $discord) {}

    public function list(Client $client): array
    {
        return $this->discord->get("/guilds/{$client->discord_guild_id}/channels");
    }

    /**
     * @param  array<int, array{id: string, type: int, allow?: string, deny?: string}>  $permissionOverwrites
     */
    public function create(
        Client $client,
        string $name,
        int $type = 0,
        ?string $parentId = null,
        array $permissionOverwrites = [],
    ): array {
        return $this->discord->post("/guilds/{$client->discord_guild_id}/channels", array_filter([
            'name' => $name,
            'type' => $type,
            'parent_id' => $parentId,
            'permission_overwrites' => $permissionOverwrites ?: null,
        ], fn ($value) => $value !== null));
    }

    public function delete(string $discordChannelId, ?string $reason = null): void
    {
        $this->discord->delete("/channels/{$discordChannelId}", reason: $reason);
    }
}
