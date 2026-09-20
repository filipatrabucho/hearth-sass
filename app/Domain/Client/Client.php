<?php

namespace App\Domain\Client;

use App\Domain\Module\Module;
use App\Domain\User\User;
use Database\Factories\ClientFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * A Client is a Discord community (guild) onboarded onto HearthGG.
 */
class Client extends Model
{
    /** @use HasFactory<ClientFactory> */
    use HasFactory;

    protected $guarded = [];

    protected static function newFactory(): ClientFactory
    {
        return ClientFactory::new();
    }

    protected function casts(): array
    {
        return [
            'trial_ends_at' => 'datetime',
        ];
    }

    /**
     * Validation rules for creating/updating a client record.
     */
    public static function validationRules(?int $id = null): array
    {
        return [
            'discord_guild_id' => 'required|string|unique:clients,discord_guild_id'.($id ? ",{$id}" : ''),
            'name' => 'required|string|max:255',
            'icon_hash' => 'nullable|string|max:255',
            'owner_user_id' => 'required|exists:users,id',
            'plan' => 'required|string|in:free,pro,enterprise',
            'status' => 'required|string|in:active,suspended,cancelled',
            'trial_ends_at' => 'nullable|date',
        ];
    }

    /**
     * Fields from the current request that are allowed to be written by
     * the generic create/update service (App\Services\ModelServiceApi).
     */
    public static function activeFields(): array
    {
        return [
            'discord_guild_id',
            'name',
            'icon_hash',
            'owner_user_id',
            'plan',
            'status',
            'trial_ends_at',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    /**
     * Users with management access to this client, with their role.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'client_user')
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * Modules this client has access to, with the enabled flag.
     */
    public function modules(): BelongsToMany
    {
        return $this->belongsToMany(Module::class, 'client_module')
            ->withPivot('is_enabled', 'enabled_at')
            ->withTimestamps();
    }

    public function hasModuleEnabled(string $key): bool
    {
        return $this->modules()
            ->where('key', $key)
            ->wherePivot('is_enabled', true)
            ->exists();
    }

    public function iconUrl(): ?string
    {
        if (! $this->icon_hash) {
            return null;
        }

        return "https://cdn.discordapp.com/icons/{$this->discord_guild_id}/{$this->icon_hash}.png";
    }
}
