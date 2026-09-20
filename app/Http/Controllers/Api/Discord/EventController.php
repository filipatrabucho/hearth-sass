<?php

namespace App\Http\Controllers\Api\Discord;

use App\Domain\Client\Client;
use App\Http\Controllers\Controller;
use App\Services\Discord\EventService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Discord scheduled events (the "eventos" module). entity_type: 1 = stage,
 * 2 = voice channel, 3 = external location - see Discord's docs for the
 * full payload shape.
 */
class EventController extends Controller
{
    public function __construct(private readonly EventService $events) {}

    private const RULES = [
        'name' => 'required|string|max:100',
        'description' => 'nullable|string|max:1000',
        'scheduled_start_time' => 'required|date',
        'scheduled_end_time' => 'nullable|date|after:scheduled_start_time',
        'privacy_level' => 'sometimes|integer|in:2',
        'entity_type' => 'required|integer|in:1,2,3',
        'channel_id' => 'required_if:entity_type,1,2|string',
        'location' => 'required_if:entity_type,3|string|max:100',
    ];

    public function index(Client $client): JsonResponse
    {
        return response()->json($this->events->list($client));
    }

    public function show(Client $client, string $discordEventId): JsonResponse
    {
        return response()->json($this->events->find($client, $discordEventId));
    }

    public function store(Request $request, Client $client): JsonResponse
    {
        $this->validate($request, self::RULES);

        $event = $this->events->create($client, $this->toDiscordPayload($request));

        return response()->json($event, 201);
    }

    public function update(Request $request, Client $client, string $discordEventId): JsonResponse
    {
        $this->validate($request, array_map(
            fn ($rule) => str_replace('required', 'sometimes', $rule),
            self::RULES,
        ));

        return response()->json($this->events->update($client, $discordEventId, $this->toDiscordPayload($request)));
    }

    public function destroy(Client $client, string $discordEventId): JsonResponse
    {
        $this->events->delete($client, $discordEventId);

        return response()->json(status: 204);
    }

    private function toDiscordPayload(Request $request): array
    {
        $payload = $request->only([
            'name', 'description', 'scheduled_start_time', 'scheduled_end_time', 'entity_type', 'channel_id',
        ]);
        $payload['privacy_level'] = 2; // GUILD_ONLY - the only level Discord currently supports.

        if ($request->filled('location')) {
            $payload['entity_metadata'] = ['location' => $request->input('location')];
        }

        return array_filter($payload, fn ($value) => $value !== null);
    }
}
