<?php

namespace App\Http\Controllers\Api;

use App\Domain\Client\Client;
use App\Domain\Warning\Warning;
use App\Http\Controllers\Controller;
use App\Services\WarningService;
use Illuminate\Http\JsonResponse;

class WarningController extends Controller
{
    public function __construct(private readonly WarningService $warnings) {}

    public function index(Client $client): JsonResponse
    {
        return response()->json($client->warnings()->latest()->get());
    }

    public function resolve(Client $client, Warning $warning): JsonResponse
    {
        abort_unless($warning->client_id === $client->id, 404);

        return response()->json($this->warnings->resolve($warning));
    }
}
