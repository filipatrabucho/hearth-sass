<?php

namespace Tests\Feature;

use App\Domain\Client\Client;
use App\Domain\Client\ClientUser;
use App\Domain\Module\Module;
use App\Domain\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientPaymentAndBotAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_set_a_modules_payment_status_when_toggling_it(): void
    {
        $owner = User::factory()->create();
        $admin = User::factory()->create();
        $client = Client::factory()->create(['owner_user_id' => $owner->id]);
        $client->users()->attach($admin, ['role' => ClientUser::ROLE_ADMIN]);
        $module = Module::factory()->create(['key' => 'events']);

        $this->actingAs($admin)
            ->postJson("/api/clients/{$client->id}/modules/{$module->id}", [
                'enabled' => true,
                'payment_status' => 'active',
                'paid_until' => now()->addMonth()->toIso8601String(),
            ])
            ->assertOk()
            ->assertJsonFragment(['payment_status' => 'active']);

        $this->assertTrue($client->fresh()->hasModuleEnabled('events'));
    }

    public function test_admin_can_record_the_bot_being_installed_on_their_guild(): void
    {
        $owner = User::factory()->create();
        $admin = User::factory()->create();
        $client = Client::factory()->create(['owner_user_id' => $owner->id]);
        $client->users()->attach($admin, ['role' => ClientUser::ROLE_ADMIN]);

        $this->assertFalse($client->isBotInstalled());

        $this->actingAs($admin)
            ->postJson("/api/clients/{$client->id}/bot/install", [
                'guild_id' => $client->discord_guild_id,
                'permissions' => '8',
            ])
            ->assertOk()
            ->assertJsonFragment(['bot_permissions' => '8']);

        $this->assertTrue($client->fresh()->isBotInstalled());
    }

    public function test_bot_install_rejects_a_mismatched_guild_id(): void
    {
        $owner = User::factory()->create();
        $admin = User::factory()->create();
        $client = Client::factory()->create(['owner_user_id' => $owner->id]);
        $client->users()->attach($admin, ['role' => ClientUser::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->postJson("/api/clients/{$client->id}/bot/install", [
                'guild_id' => 'not-this-guild',
                'permissions' => '8',
            ])
            ->assertStatus(422);

        $this->assertFalse($client->fresh()->isBotInstalled());
    }
}
