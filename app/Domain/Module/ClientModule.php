<?php

namespace App\Domain\Module;

use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Pivot for the client_module table: whether a client has a module
 * switched on, and the payment state backing that access.
 */
class ClientModule extends Pivot
{
    public const STATUS_TRIALING = 'trialing';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_PAST_DUE = 'past_due';

    public const STATUS_CANCELED = 'canceled';

    public const STATUSES = [
        self::STATUS_TRIALING,
        self::STATUS_ACTIVE,
        self::STATUS_PAST_DUE,
        self::STATUS_CANCELED,
    ];

    protected $table = 'client_module';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'enabled_at' => 'datetime',
            'paid_until' => 'datetime',
        ];
    }

    /**
     * Whether this module is actually usable right now: switched on, and
     * either not on a payment plan yet (paid_until null, e.g. a manually
     * granted module) or still within the paid period.
     */
    public function isActive(): bool
    {
        if (! $this->is_enabled || $this->payment_status === self::STATUS_CANCELED) {
            return false;
        }

        return $this->paid_until === null || $this->paid_until->isFuture();
    }
}
