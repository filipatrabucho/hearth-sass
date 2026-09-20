<?php

namespace App\Services\Discord;

use App\Exceptions\DiscordApiException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Thin wrapper over the Discord REST API, authenticated as the single
 * HearthGG bot (config('services.discord.bot_token')). Every module
 * service (MemberService, RoleService, ...) is built on top of this -
 * none of them talk to Discord directly.
 *
 * The bot must actually be a member of a client's guild for these calls
 * to succeed; see Client::isBotInstalled().
 */
class DiscordClient
{
    private const BASE_URL = 'https://discord.com/api/v10';

    public function get(string $uri, array $query = []): array
    {
        return $this->request('get', $uri, ['query' => $query]);
    }

    public function post(string $uri, array $payload = [], ?string $reason = null): array
    {
        return $this->request('post', $uri, ['json' => $payload, 'headers' => $this->reasonHeader($reason)]);
    }

    public function patch(string $uri, array $payload = [], ?string $reason = null): array
    {
        return $this->request('patch', $uri, ['json' => $payload, 'headers' => $this->reasonHeader($reason)]);
    }

    public function put(string $uri, array $payload = [], ?string $reason = null): array
    {
        return $this->request('put', $uri, ['json' => $payload, 'headers' => $this->reasonHeader($reason)]);
    }

    public function delete(string $uri, array $payload = [], ?string $reason = null): void
    {
        $this->request('delete', $uri, ['json' => $payload, 'headers' => $this->reasonHeader($reason)]);
    }

    /**
     * Discord logs moderation reasons via this header, not a body field.
     */
    private function reasonHeader(?string $reason): array
    {
        return $reason ? ['X-Audit-Log-Reason' => $reason] : [];
    }

    /**
     * @return array<mixed>
     */
    private function request(string $method, string $uri, array $options): array
    {
        /** @var Response $response */
        $response = $this->client()->send($method, $uri, $options);

        if (! $response->successful()) {
            throw DiscordApiException::fromResponse($method, $uri, $response);
        }

        return $response->json() ?? [];
    }

    private function client(): PendingRequest
    {
        return Http::baseUrl(self::BASE_URL)
            ->withToken($this->botToken(), 'Bot')
            ->acceptJson()
            ->timeout(15);
    }

    private function botToken(): string
    {
        $token = config('services.discord.bot_token');

        if (! $token) {
            throw new DiscordApiException('DISCORD_BOT_TOKEN is not configured.');
        }

        return $token;
    }
}
