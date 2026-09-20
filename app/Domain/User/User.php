<?php

namespace App\Domain\User;

use App\Domain\Client\Client;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $guarded = [];

    protected $hidden = [
        'access_token',
        'refresh_token',
    ];

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }

    protected function casts(): array
    {
        return [
            'is_super_admin' => 'boolean',
            'access_token' => 'encrypted',
            'refresh_token' => 'encrypted',
            'token_expires_at' => 'datetime',
            'last_login_at' => 'datetime',
        ];
    }

    /**
     * Validation rules for creating/updating a user record.
     */
    public static function validationRules(?int $id = null): array
    {
        return [
            'discord_id' => 'required|string|unique:users,discord_id'.($id ? ",{$id}" : ''),
            'username' => 'required|string|max:255',
            'discriminator' => 'nullable|string|max:10',
            'global_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'avatar_hash' => 'nullable|string|max:255',
            'is_super_admin' => 'sometimes|boolean',
        ];
    }

    /**
     * Fields from the current request that are allowed to be written by
     * the generic create/update service (App\Services\ModelServiceApi).
     */
    public static function activeFields(): array
    {
        return [
            'discord_id',
            'username',
            'discriminator',
            'global_name',
            'email',
            'avatar_hash',
            'is_super_admin',
        ];
    }

    /**
     * Clients this user belongs to, with their role in each one.
     */
    public function clients(): BelongsToMany
    {
        return $this->belongsToMany(Client::class, 'client_user')
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * Clients this user is the Discord-side owner of.
     */
    public function ownedClients(): HasMany
    {
        return $this->hasMany(Client::class, 'owner_user_id');
    }

    public function avatarUrl(): ?string
    {
        if (! $this->avatar_hash) {
            return null;
        }

        return "https://cdn.discordapp.com/avatars/{$this->discord_id}/{$this->avatar_hash}.png";
    }
}
