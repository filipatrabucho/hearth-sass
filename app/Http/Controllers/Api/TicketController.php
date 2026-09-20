<?php

namespace App\Http\Controllers\Api;

use App\Domain\Client\Client;
use App\Domain\Ticket\Ticket;
use App\Http\Controllers\Controller;
use App\Services\TicketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function __construct(private readonly TicketService $tickets) {}

    public function index(Client $client): JsonResponse
    {
        return response()->json($client->tickets()->latest()->get());
    }

    public function show(Client $client, Ticket $ticket): JsonResponse
    {
        $this->assertBelongsToClient($client, $ticket);

        return response()->json($ticket->load('messages', 'assignee'));
    }

    public function store(Request $request, Client $client): JsonResponse
    {
        $this->validate($request, [
            'discord_user_id' => 'required|string',
            'subject' => 'required|string|max:255',
            'message' => 'nullable|string',
        ]);

        $ticket = $this->tickets->open(
            $client,
            $request->input('discord_user_id'),
            $request->input('subject'),
            $request->input('message'),
        );

        return response()->json($ticket, 201);
    }

    public function reply(Request $request, Client $client, Ticket $ticket): JsonResponse
    {
        $this->assertBelongsToClient($client, $ticket);
        $this->validate($request, ['body' => 'required|string']);

        $message = $this->tickets->reply($ticket, staffUser: $request->user(), body: $request->input('body'));

        return response()->json($message, 201);
    }

    public function updateStatus(Request $request, Client $client, Ticket $ticket): JsonResponse
    {
        $this->assertBelongsToClient($client, $ticket);
        $this->validate($request, ['status' => 'required|string|in:'.implode(',', Ticket::STATUSES)]);

        return response()->json($this->tickets->updateStatus($ticket, $request->input('status')));
    }

    public function close(Client $client, Ticket $ticket): JsonResponse
    {
        $this->assertBelongsToClient($client, $ticket);

        return response()->json($this->tickets->close($ticket));
    }

    private function assertBelongsToClient(Client $client, Ticket $ticket): void
    {
        abort_unless($ticket->client_id === $client->id, 404);
    }
}
