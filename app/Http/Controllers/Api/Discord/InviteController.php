<?php

namespace App\Http\Controllers\Api\Discord;

use App\Domain\Client\Client;
use App\Http\Controllers\Controller;
use App\Services\Discord\InviteService;
use Illuminate\Http\JsonResponse;

class InviteController extends Controller
{
    public function __construct(private readonly InviteService $invites) {}

    public function index(Client $client): JsonResponse
    {
        return response()->json($client->invites()->orderByDesc('uses')->get());
    }

    public function sync(Client $client): JsonResponse
    {
        return response()->json(['synced' => $this->invites->sync($client)]);
    }
}
