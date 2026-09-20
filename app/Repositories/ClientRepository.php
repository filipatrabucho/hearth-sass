<?php

namespace App\Repositories;

use App\Domain\Client\Client;
use App\Domain\Module\Module;
use App\Domain\User\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * Heavier client (tenant) queries and cross-table module/membership
 * management that don't belong in the thin Domain models.
 */
class ClientRepository
{
    /**
     * Clients a user can switch into: every client for a super admin,
     * otherwise only the ones they're a member of.
     */
    public function forUser(User $user): Collection
    {
        if ($user->is_super_admin) {
            return Client::query()->orderBy('name')->get();
        }

        return $user->clients()->orderBy('name')->get();
    }

    public function enabledModules(Client $client): Collection
    {
        return $client->modules()->wherePivot('is_enabled', true)->get();
    }

    public function setModuleEnabled(Client $client, Module $module, bool $enabled): void
    {
        $client->modules()->syncWithoutDetaching([
            $module->id => [
                'is_enabled' => $enabled,
                'enabled_at' => $enabled ? now() : null,
            ],
        ]);
    }

    public function addMember(Client $client, User $user, string $role): void
    {
        $client->users()->syncWithoutDetaching([
            $user->id => ['role' => $role],
        ]);
    }

    public function removeMember(Client $client, User $user): void
    {
        $client->users()->detach($user->id);
    }
}
