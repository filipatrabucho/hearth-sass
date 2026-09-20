<?php

namespace App\Domain\Warning;

use App\Domain\Client\Client;
use App\Domain\User\User;
use Database\Factories\WarningFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Warning extends Model
{
    /** @use HasFactory<WarningFactory> */
    use HasFactory;

    protected $guarded = [];

    protected static function newFactory(): WarningFactory
    {
        return WarningFactory::new();
    }

    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
        ];
    }

    public static function validationRules(?int $id = null): array
    {
        return [
            'client_id' => 'required|exists:clients,id',
            'discord_user_id' => 'required|string',
            'moderator_id' => 'nullable|exists:users,id',
            'reason' => 'required|string',
        ];
    }

    public static function activeFields(): array
    {
        return [
            'client_id',
            'discord_user_id',
            'moderator_id',
            'reason',
            'resolved_at',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderator_id');
    }

    public function isResolved(): bool
    {
        return $this->resolved_at !== null;
    }
}
