<?php

namespace App\Domain\Invite;

use App\Domain\Client\Client;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invite extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'synced_at' => 'datetime',
        ];
    }

    public static function validationRules(?int $id = null): array
    {
        return [
            'client_id' => 'required|exists:clients,id',
            'code' => 'required|string',
            'inviter_discord_id' => 'nullable|string',
            'uses' => 'sometimes|integer|min:0',
            'max_uses' => 'nullable|integer|min:0',
            'expires_at' => 'nullable|date',
        ];
    }

    public static function activeFields(): array
    {
        return [
            'client_id',
            'code',
            'inviter_discord_id',
            'uses',
            'max_uses',
            'expires_at',
            'synced_at',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
