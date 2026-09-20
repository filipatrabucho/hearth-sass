<?php

namespace App\Domain\Client;

use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Pivot for the client_user table: a user's role within a given client.
 */
class ClientUser extends Pivot
{
    public const ROLE_OWNER = 'owner';

    public const ROLE_ADMIN = 'admin';

    public const ROLE_STAFF = 'staff';

    public const ROLES = [
        self::ROLE_OWNER,
        self::ROLE_ADMIN,
        self::ROLE_STAFF,
    ];

    protected $table = 'client_user';

    protected $guarded = [];
}
