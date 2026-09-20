<?php

namespace Tests\Feature;

use App\Domain\Client\Client;
use App\Domain\Client\ClientUser;
use App\Domain\Module\Module;
use App\Domain\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_list_clients(): void
    {
        $this->getJson('/api/clients')->assertStatus(401);
    }

    public function test_super_admin_can_onboard_a_client(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $owner = User::factory()->create();

        $response = $this->actingAs($admin)->postJson('/api/clients', [
            'discord_guild_id' => '123456789',
            'name' => 'Test Guild',
            'owner_user_id' => $owner->id,
            'plan' => 'free',
            'status' => 'active',
        ]);

        $response->assertCreated()->assertJsonFragment(['name' => 'Test Guild']);
        $this->assertDatabaseHas('clients', ['discord_guild_id' => '123456789']);
    }

    public function test_staff_member_cannot_update_client_settings(): void
    {
        $owner = User::factory()->create();
        $staff = User::factory()->create();
        $client = Client::factory()->create(['owner_user_id' => $owner->id]);
        $client->users()->attach($staff, ['role' => ClientUser::ROLE_STAFF]);

        $this->actingAs($staff)
            ->putJson("/api/clients/{$client->id}", ['name' => 'Renamed'])
            ->assertStatus(403);
    }

    public function test_admin_member_can_toggle_a_module_for_their_client(): void
    {
        $owner = User::factory()->create();
        $admin = User::factory()->create();
        $client = Client::factory()->create(['owner_user_id' => $owner->id]);
        $client->users()->attach($admin, ['role' => ClientUser::ROLE_ADMIN]);
        $module = Module::factory()->create(['key' => 'events']);

        $this->actingAs($admin)
            ->postJson("/api/clients/{$client->id}/modules/{$module->id}", ['enabled' => true])
            ->assertOk();

        $this->assertDatabaseHas('client_module', [
            'client_id' => $client->id,
            'module_id' => $module->id,
            'is_enabled' => true,
        ]);
    }

    public function test_user_outside_the_client_cannot_view_it(): void
    {
        $owner = User::factory()->create();
        $outsider = User::factory()->create();
        $client = Client::factory()->create(['owner_user_id' => $owner->id]);

        $this->actingAs($outsider)
            ->getJson("/api/clients/{$client->id}")
            ->assertStatus(403);
    }
}
