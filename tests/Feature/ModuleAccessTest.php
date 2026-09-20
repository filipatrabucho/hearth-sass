<?php

namespace Tests\Feature;

use App\Domain\Client\Client;
use App\Domain\Client\ClientUser;
use App\Domain\Member\Member;
use App\Domain\Module\Module;
use App\Domain\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModuleAccessTest extends TestCase
{
    use RefreshDatabase;

    private function staffClientWithModule(string $key, array $pivot = []): array
    {
        $owner = User::factory()->create();
        $staff = User::factory()->create();
        $client = Client::factory()->create([
            'owner_user_id' => $owner->id,
            'bot_installed_at' => now(),
        ]);
        $client->users()->attach($staff, ['role' => ClientUser::ROLE_STAFF]);

        $module = Module::factory()->create(['key' => $key]);
        $client->modules()->attach($module, array_merge([
            'is_enabled' => true,
            'payment_status' => 'active',
        ], $pivot));

        return [$client, $staff];
    }

    public function test_module_not_enabled_returns_payment_required(): void
    {
        $owner = User::factory()->create();
        $staff = User::factory()->create();
        $client = Client::factory()->create(['owner_user_id' => $owner->id, 'bot_installed_at' => now()]);
        $client->users()->attach($staff, ['role' => ClientUser::ROLE_STAFF]);
        Module::factory()->create(['key' => 'members']);

        $this->actingAs($staff)
            ->getJson("/api/clients/{$client->id}/members")
            ->assertStatus(402);
    }

    public function test_expired_payment_returns_payment_required(): void
    {
        [$client, $staff] = $this->staffClientWithModule('members', [
            'payment_status' => 'active',
            'paid_until' => now()->subDay(),
        ]);

        $this->actingAs($staff)
            ->getJson("/api/clients/{$client->id}/members")
            ->assertStatus(402);
    }

    public function test_bot_not_installed_returns_conflict(): void
    {
        $owner = User::factory()->create();
        $staff = User::factory()->create();
        $client = Client::factory()->create(['owner_user_id' => $owner->id]); // no bot_installed_at
        $client->users()->attach($staff, ['role' => ClientUser::ROLE_STAFF]);
        $module = Module::factory()->create(['key' => 'members']);
        $client->modules()->attach($module, ['is_enabled' => true, 'payment_status' => 'active']);

        $this->actingAs($staff)
            ->getJson("/api/clients/{$client->id}/members")
            ->assertStatus(409);
    }

    public function test_paid_active_module_with_bot_installed_is_reachable(): void
    {
        [$client, $staff] = $this->staffClientWithModule('members');
        Member::factory()->create(['client_id' => $client->id]);

        $this->actingAs($staff)
            ->getJson("/api/clients/{$client->id}/members")
            ->assertOk()
            ->assertJsonCount(1);
    }

    public function test_outsider_gets_forbidden_before_payment_is_even_checked(): void
    {
        $owner = User::factory()->create();
        $outsider = User::factory()->create();
        $client = Client::factory()->create(['owner_user_id' => $owner->id, 'bot_installed_at' => now()]);
        $module = Module::factory()->create(['key' => 'members']);
        $client->modules()->attach($module, ['is_enabled' => true, 'payment_status' => 'active']);

        $this->actingAs($outsider)
            ->getJson("/api/clients/{$client->id}/members")
            ->assertStatus(403);
    }
}
