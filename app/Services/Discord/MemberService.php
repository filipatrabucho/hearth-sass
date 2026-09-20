<?php

namespace App\Services\Discord;

use App\Domain\Client\Client;
use App\Domain\Member\Member;
use Carbon\Carbon;

/**
 * Guild members: listing, moderation (kick/ban/unban/timeout) and the
 * local Member cache used by the "membros" module.
 */
class MemberService
{
    public function __construct(private readonly DiscordClient $discord) {}

    /**
     * Raw member list straight from Discord (max 1000 per Discord's own limit).
     */
    public function list(Client $client, int $limit = 1000, ?string $after = null): array
    {
        return $this->discord->get("/guilds/{$client->discord_guild_id}/members", array_filter([
            'limit' => $limit,
            'after' => $after,
        ]));
    }

    public function bans(Client $client): array
    {
        return $this->discord->get("/guilds/{$client->discord_guild_id}/bans");
    }

    public function kick(Client $client, string $discordUserId, ?string $reason = null): void
    {
        $this->discord->delete("/guilds/{$client->discord_guild_id}/members/{$discordUserId}", reason: $reason);
    }

    public function ban(Client $client, string $discordUserId, ?string $reason = null, int $deleteMessageSeconds = 0): void
    {
        $this->discord->put(
            "/guilds/{$client->discord_guild_id}/bans/{$discordUserId}",
            ['delete_message_seconds' => $deleteMessageSeconds],
            reason: $reason,
        );
    }

    public function unban(Client $client, string $discordUserId, ?string $reason = null): void
    {
        $this->discord->delete("/guilds/{$client->discord_guild_id}/bans/{$discordUserId}", reason: $reason);
    }

    /**
     * A null $until lifts an existing timeout.
     */
    public function timeout(Client $client, string $discordUserId, ?Carbon $until, ?string $reason = null): void
    {
        $this->discord->patch(
            "/guilds/{$client->discord_guild_id}/members/{$discordUserId}",
            ['communication_disabled_until' => $until?->toIso8601String()],
            reason: $reason,
        );
    }

    /**
     * Refreshes the local member cache from Discord. Returns how many
     * members were synced.
     */
    public function sync(Client $client): int
    {
        $synced = 0;
        $after = null;

        do {
            $page = $this->list($client, 1000, $after);

            foreach ($page as $entry) {
                $user = $entry['user'] ?? [];

                if (empty($user['id'])) {
                    continue;
                }

                Member::updateOrCreate(
                    ['client_id' => $client->id, 'discord_user_id' => $user['id']],
                    [
                        'username' => $user['username'] ?? $user['id'],
                        'global_name' => $user['global_name'] ?? null,
                        'avatar_hash' => $user['avatar'] ?? null,
                        'role_ids' => $entry['roles'] ?? [],
                        'joined_discord_at' => isset($entry['joined_at']) ? Carbon::parse($entry['joined_at']) : null,
                        'synced_at' => now(),
                    ]
                );

                $synced++;
                $after = $user['id'];
            }
        } while (count($page) === 1000);

        return $synced;
    }
}
