<?php

namespace App\Http\Controllers\Api\Discord;

use App\Domain\Client\Client;
use App\Http\Controllers\Controller;
use App\Services\Discord\MemberService;
use App\Services\WarningService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MemberController extends Controller
{
    public function __construct(
        private readonly MemberService $members,
        private readonly WarningService $warnings,
    ) {}

    public function index(Client $client): JsonResponse
    {
        return response()->json($client->members()->orderBy('username')->get());
    }

    public function sync(Client $client): JsonResponse
    {
        return response()->json(['synced' => $this->members->sync($client)]);
    }

    public function bans(Client $client): JsonResponse
    {
        return response()->json($this->members->bans($client));
    }

    public function history(Client $client, string $discordUserId): JsonResponse
    {
        return response()->json(
            $client->warnings()->where('discord_user_id', $discordUserId)->latest()->get()
        );
    }

    public function kick(Request $request, Client $client, string $discordUserId): JsonResponse
    {
        $this->validate($request, ['reason' => 'nullable|string|max:512']);

        $this->members->kick($client, $discordUserId, $request->input('reason'));

        return response()->json(status: 204);
    }

    public function ban(Request $request, Client $client, string $discordUserId): JsonResponse
    {
        $this->validate($request, [
            'reason' => 'nullable|string|max:512',
            'delete_message_seconds' => 'sometimes|integer|min:0|max:604800',
        ]);

        $this->members->ban(
            $client,
            $discordUserId,
            $request->input('reason'),
            $request->integer('delete_message_seconds'),
        );

        return response()->json(status: 204);
    }

    public function unban(Request $request, Client $client, string $discordUserId): JsonResponse
    {
        $this->validate($request, ['reason' => 'nullable|string|max:512']);

        $this->members->unban($client, $discordUserId, $request->input('reason'));

        return response()->json(status: 204);
    }

    public function timeout(Request $request, Client $client, string $discordUserId): JsonResponse
    {
        $this->validate($request, [
            'until' => 'nullable|date',
            'reason' => 'nullable|string|max:512',
        ]);

        $until = $request->filled('until') ? Carbon::parse($request->input('until')) : null;

        $this->members->timeout($client, $discordUserId, $until, $request->input('reason'));

        return response()->json(status: 204);
    }

    public function warn(Request $request, Client $client, string $discordUserId): JsonResponse
    {
        $this->validate($request, ['reason' => 'required|string']);

        $warning = $this->warnings->warn($client, $discordUserId, $request->user(), $request->input('reason'));

        return response()->json($warning, 201);
    }
}
