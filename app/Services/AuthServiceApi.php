<?php

namespace App\Services;

use App\Domain\Client\Client;
use App\Domain\Client\ClientUser;
use App\Domain\User\User;
use Illuminate\Http\Request;

/**
 * Multi-tenant permission checks: which client the current request is
 * acting on, and whether the authenticated user is allowed to act on it.
 */
class AuthServiceApi
{
    private const ROLE_LEVELS = [
        ClientUser::ROLE_STAFF => 1,
        ClientUser::ROLE_ADMIN => 2,
        ClientUser::ROLE_OWNER => 3,
    ];

    /**
     * Whether $user is at least $minimumRole (owner > admin > staff) on
     * $client. HearthGG super admins (the SaaS owner's own accounts)
     * always pass, so they can manage every client.
     */
    public function userHasPermission(User $user, Client $client, string $minimumRole = ClientUser::ROLE_STAFF): bool
    {
        if ($user->is_super_admin) {
            return true;
        }

        $role = $user->clients()
            ->where('clients.id', $client->id)
            ->first()
            ?->pivot
            ?->role;

        if (! $role) {
            return false;
        }

        return (self::ROLE_LEVELS[$role] ?? 0) >= (self::ROLE_LEVELS[$minimumRole] ?? PHP_INT_MAX);
    }

    /**
     * Resolve the client (tenant) the current request is acting on from
     * the X-Client-Id header or a {client} route parameter, verifying the
     * authenticated user actually has access to it.
     */
    public function resolveClient(Request $request, User $user): ?Client
    {
        $clientId = $request->route('client') ?? $request->header('X-Client-Id');

        if ($clientId instanceof Client) {
            $client = $clientId;
        } elseif ($clientId) {
            $client = Client::find($clientId);
        } else {
            return null;
        }

        if (! $client) {
            return null;
        }

        if (! $user->is_super_admin && ! $user->clients()->where('clients.id', $client->id)->exists()) {
            return null;
        }

        return $client;
    }
}
