<?php

namespace App\Domain\Ticket;

use App\Domain\User\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketMessage extends Model
{
    protected $guarded = [];

    public static function validationRules(?int $id = null): array
    {
        return [
            'ticket_id' => 'required|exists:tickets,id',
            'author_user_id' => 'nullable|exists:users,id',
            'discord_user_id' => 'nullable|string',
            'body' => 'required|string',
        ];
    }

    public static function activeFields(): array
    {
        return [
            'ticket_id',
            'author_user_id',
            'discord_user_id',
            'body',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_user_id');
    }
}
