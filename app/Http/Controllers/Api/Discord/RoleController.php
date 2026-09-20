<?php

namespace App\Http\Controllers\Api\Discord;

use App\Domain\Client\Client;
use App\Http\Controllers\Controller;
use App\Services\Discord\RoleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function __construct(private readonly RoleService $roles) {}

    public function index(Client $client): JsonResponse
    {
        return response()->json($this->roles->list($client));
    }

    public function staff(Request $request, Client $client): JsonResponse
    {
        $this->validate($request, ['role_ids' => 'required|array', 'role_ids.*' => 'string']);

        return response()->json($this->roles->staffMembers($client, $request->input('role_ids'))->values());
    }

    public function addToMember(Request $request, Client $client, string $discordUserId, string $roleId): JsonResponse
    {
        $this->validate($request, ['reason' => 'nullable|string|max:512']);

        $this->roles->addToMember($client, $discordUserId, $roleId, $request->input('reason'));

        return response()->json(status: 204);
    }

    public function removeFromMember(Request $request, Client $client, string $discordUserId, string $roleId): JsonResponse
    {
        $this->validate($request, ['reason' => 'nullable|string|max:512']);

        $this->roles->removeFromMember($client, $discordUserId, $roleId, $request->input('reason'));

        return response()->json(status: 204);
    }
}
