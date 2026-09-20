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
     * otherwise only the ones they're a member of. Modules are eager
     * loaded so the caller (the client switcher, or the HearthGG "which
     * clients are active/paying" overview) sees per-module payment state
     * without an extra round trip.
     */
    public function forUser(User $user, ?string $status = null): Collection
    {
        $query = $user->is_super_admin ? Client::query() : $user->clients();

        return $query->with('modules')
            ->when($status, fn ($query) => $query->where('status', $status))
            ->orderBy('name')
            ->get();
    }

    public function enabledModules(Client $client): Collection
    {
        return $client->modules()->wherePivot('is_enabled', true)->get();
    }

    /**
     * Switches a module on/off for a client and (optionally) records the
     * payment state backing that access - see App\Domain\Module\ClientModule.
     */
    public function setModuleEnabled(Client $client, Module $module, bool $enabled, ?string $paymentStatus = null, ?string $paidUntil = null): void
    {
        $pivot = [
            'is_enabled' => $enabled,
            'enabled_at' => $enabled ? now() : null,
        ];

        if ($paymentStatus !== null) {
            $pivot['payment_status'] = $paymentStatus;
        }

        if ($paidUntil !== null) {
            $pivot['paid_until'] = $paidUntil;
        }

        $client->modules()->syncWithoutDetaching([$module->id => $pivot]);
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
