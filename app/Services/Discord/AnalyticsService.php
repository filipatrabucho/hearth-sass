<?php

namespace App\Services\Discord;

use App\Domain\Client\Client;
use App\Domain\Ticket\Ticket;

class AnalyticsService
{
    public function __construct(
        private readonly DiscordClient $discord,
        private readonly EventService $events,
    ) {}

    /**
     * Live counts straight from Discord (approximate_member_count, ...).
     */
    public function guildStats(Client $client): array
    {
        return $this->discord->get("/guilds/{$client->discord_guild_id}", ['with_counts' => 'true']);
    }

    public function auditLog(Client $client, array $filters = []): array
    {
        return $this->discord->get("/guilds/{$client->discord_guild_id}/audit-logs", $filters);
    }

    /**
     * HearthGG's own dashboard numbers: a mix of the live Discord guild
     * stats and what we track locally per module.
     */
    public function summary(Client $client): array
    {
        return [
            'guild' => $this->guildStats($client),
            'members_cached' => $client->members()->count(),
            'open_tickets' => $client->tickets()->where('status', '!=', Ticket::STATUS_CLOSED)->count(),
            'published_posts' => $client->posts()->where('status', 'published')->count(),
            'unresolved_warnings' => $client->warnings()->whereNull('resolved_at')->count(),
            'upcoming_events' => count($this->events->list($client)),
        ];
    }
}
