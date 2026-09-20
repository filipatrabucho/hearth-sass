<?php

namespace App\Domain\Client;

use App\Domain\Invite\Invite;
use App\Domain\Member\Member;
use App\Domain\Module\ClientModule;
use App\Domain\Module\Module;
use App\Domain\Post\Post;
use App\Domain\Ticket\Ticket;
use App\Domain\User\User;
use App\Domain\Warning\Warning;
use Database\Factories\ClientFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A Client is a Discord community (guild) onboarded onto HearthGG.
 */
class Client extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_SUSPENDED = 'suspended';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_SUSPENDED,
        self::STATUS_CANCELLED,
    ];

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
            'bot_installed_at' => 'datetime',
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
            'status' => 'required|string|in:'.implode(',', self::STATUSES),
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
     * Modules this client has access to, with the enabled flag and the
     * payment state backing it.
     */
    public function modules(): BelongsToMany
    {
        return $this->belongsToMany(Module::class, 'client_module')
            ->using(ClientModule::class)
            ->withPivot('is_enabled', 'payment_status', 'paid_until', 'enabled_at')
            ->withTimestamps();
    }

    public function members(): HasMany
    {
        return $this->hasMany(Member::class);
    }

    public function invites(): HasMany
    {
        return $this->hasMany(Invite::class);
    }

    public function warnings(): HasMany
    {
        return $this->hasMany(Warning::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    /**
     * Whether the client still has real, paid access to a module - not
     * just whether it's switched on. This is the gate every Discord-bot
     * and app module endpoint checks (see App\Http\Middleware\EnsureModuleAccess).
     */
    public function hasModuleEnabled(string $key): bool
    {
        $pivot = $this->modules()->where('key', $key)->first()?->pivot;

        return $pivot instanceof ClientModule && $pivot->isActive();
    }

    public function isBotInstalled(): bool
    {
        return $this->bot_installed_at !== null;
    }

    /**
     * The account-level gate: a suspended/cancelled client loses access to
     * every module regardless of individual module payment state. This is
     * set by a HearthGG super admin today (activate()/suspend()/cancel())
     * and will be driven by Stripe subscription events once billing moves
     * there (stripe_customer_id/stripe_subscription_id are already in
     * place for that).
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function activate(): void
    {
        $this->forceFill(['status' => self::STATUS_ACTIVE, 'suspended_reason' => null])->save();
    }

    public function suspend(?string $reason = null): void
    {
        $this->forceFill(['status' => self::STATUS_SUSPENDED, 'suspended_reason' => $reason])->save();
    }

    public function cancel(): void
    {
        $this->forceFill(['status' => self::STATUS_CANCELLED])->save();
    }

    public function recordBotInstall(string $permissions): void
    {
        $this->forceFill([
            'bot_permissions' => $permissions,
            'bot_installed_at' => now(),
        ])->save();
    }

    public function iconUrl(): ?string
    {
        if (! $this->icon_hash) {
            return null;
        }

        return "https://cdn.discordapp.com/icons/{$this->discord_guild_id}/{$this->icon_hash}.png";
    }
}
