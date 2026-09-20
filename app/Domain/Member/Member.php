<?php

namespace App\Domain\Member;

use App\Domain\Client\Client;
use Database\Factories\MemberFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A cached snapshot of a Discord guild member, refreshed by
 * App\Services\Discord\MemberService::sync(). Not the source of truth -
 * Discord is - just fast to list/search from.
 */
class Member extends Model
{
    /** @use HasFactory<MemberFactory> */
    use HasFactory;

    protected $guarded = [];

    protected static function newFactory(): MemberFactory
    {
        return MemberFactory::new();
    }

    protected function casts(): array
    {
        return [
            'role_ids' => 'array',
            'joined_discord_at' => 'datetime',
            'synced_at' => 'datetime',
        ];
    }

    public static function validationRules(?int $id = null): array
    {
        return [
            'client_id' => 'required|exists:clients,id',
            'discord_user_id' => 'required|string',
            'username' => 'required|string|max:255',
            'global_name' => 'nullable|string|max:255',
            'avatar_hash' => 'nullable|string|max:255',
        ];
    }

    public static function activeFields(): array
    {
        return [
            'client_id',
            'discord_user_id',
            'username',
            'global_name',
            'avatar_hash',
            'role_ids',
            'joined_discord_at',
            'synced_at',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function hasRole(string $roleId): bool
    {
        return in_array($roleId, $this->role_ids ?? [], true);
    }
}
