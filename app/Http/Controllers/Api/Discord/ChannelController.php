<?php

namespace App\Http\Controllers\Api\Discord;

use App\Domain\Client\Client;
use App\Http\Controllers\Controller;
use App\Services\Discord\ChannelService;
use Illuminate\Http\JsonResponse;

class ChannelController extends Controller
{
    public function __construct(private readonly ChannelService $channels) {}

    public function index(Client $client): JsonResponse
    {
        return response()->json($this->channels->list($client));
    }
}
