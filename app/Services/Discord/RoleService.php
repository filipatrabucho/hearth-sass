<?php

namespace App\Services\Discord;

use App\Domain\Client\Client;
use Illuminate\Support\Collection;

class RoleService
{
    public function __construct(private readonly DiscordClient $discord) {}

    public function list(Client $client): array
    {
        return $this->discord->get("/guilds/{$client->discord_guild_id}/roles");
    }

    public function addToMember(Client $client, string $discordUserId, string $roleId, ?string $reason = null): void
    {
        $this->discord->put(
            "/guilds/{$client->discord_guild_id}/members/{$discordUserId}/roles/{$roleId}",
            reason: $reason,
        );
    }

    public function removeFromMember(Client $client, string $discordUserId, string $roleId, ?string $reason = null): void
    {
        $this->discord->delete(
            "/guilds/{$client->discord_guild_id}/members/{$discordUserId}/roles/{$roleId}",
            reason: $reason,
        );
    }

    /**
     * Cached members holding any of the given staff role IDs.
     */
    public function staffMembers(Client $client, array $staffRoleIds): Collection
    {
        return $client->members()
            ->get()
            ->filter(fn ($member) => array_intersect($staffRoleIds, $member->role_ids ?? []) !== []);
    }
}
