<?php

namespace App\Domain\Ticket;

use App\Domain\Client\Client;
use App\Domain\User\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ticket extends Model
{
    public const STATUS_OPEN = 'open';

    public const STATUS_PENDING = 'pending';

    public const STATUS_RESOLVED = 'resolved';

    public const STATUS_CLOSED = 'closed';

    public const STATUSES = [
        self::STATUS_OPEN,
        self::STATUS_PENDING,
        self::STATUS_RESOLVED,
        self::STATUS_CLOSED,
    ];

    protected $guarded = [];

    public static function validationRules(?int $id = null): array
    {
        return [
            'client_id' => 'required|exists:clients,id',
            'discord_user_id' => 'required|string',
            'subject' => 'required|string|max:255',
            'status' => 'sometimes|string|in:'.implode(',', self::STATUSES),
            'assigned_user_id' => 'nullable|exists:users,id',
        ];
    }

    public static function activeFields(): array
    {
        return [
            'client_id',
            'discord_user_id',
            'subject',
            'status',
            'assigned_user_id',
            'discord_channel_id',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(TicketMessage::class)->orderBy('created_at');
    }

    public function isOpen(): bool
    {
        return in_array($this->status, [self::STATUS_OPEN, self::STATUS_PENDING], true);
    }
}
