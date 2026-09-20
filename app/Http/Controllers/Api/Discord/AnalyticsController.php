<?php

namespace App\Http\Controllers\Api\Discord;

use App\Domain\Client\Client;
use App\Http\Controllers\Controller;
use App\Services\Discord\AnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function __construct(private readonly AnalyticsService $analytics) {}

    public function summary(Client $client): JsonResponse
    {
        return response()->json($this->analytics->summary($client));
    }

    public function guildStats(Client $client): JsonResponse
    {
        return response()->json($this->analytics->guildStats($client));
    }

    public function auditLog(Request $request, Client $client): JsonResponse
    {
        $this->validate($request, [
            'action_type' => 'sometimes|integer',
            'user_id' => 'sometimes|string',
            'limit' => 'sometimes|integer|min:1|max:100',
        ]);

        return response()->json($this->analytics->auditLog($client, $request->only(['action_type', 'user_id', 'limit'])));
    }
}
