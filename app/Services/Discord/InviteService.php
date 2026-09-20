<?php

namespace App\Services\Discord;

use App\Domain\Client\Client;
use App\Domain\Invite\Invite;
use Carbon\Carbon;

class InviteService
{
    public function __construct(private readonly DiscordClient $discord) {}

    public function list(Client $client): array
    {
        return $this->discord->get("/guilds/{$client->discord_guild_id}/invites");
    }

    /**
     * Refreshes the local invite cache from Discord. Returns how many
     * invites were synced.
     */
    public function sync(Client $client): int
    {
        $invites = $this->list($client);

        foreach ($invites as $invite) {
            Invite::updateOrCreate(
                ['client_id' => $client->id, 'code' => $invite['code']],
                [
                    'inviter_discord_id' => $invite['inviter']['id'] ?? null,
                    'uses' => $invite['uses'] ?? 0,
                    'max_uses' => $invite['max_uses'] ?: null,
                    'expires_at' => isset($invite['expires_at']) ? Carbon::parse($invite['expires_at']) : null,
                    'synced_at' => now(),
                ]
            );
        }

        return count($invites);
    }
}
