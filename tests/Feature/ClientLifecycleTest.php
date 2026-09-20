<?php

namespace Tests\Feature;

use App\Domain\Client\Client;
use App\Domain\Client\ClientUser;
use App\Domain\Member\Member;
use App\Domain\Module\Module;
use App\Domain\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_a_super_admin_can_suspend_a_client(): void
    {
        $owner = User::factory()->create();
        $admin = User::factory()->create();
        $client = Client::factory()->create(['owner_user_id' => $owner->id]);
        $client->users()->attach($admin, ['role' => ClientUser::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->postJson("/api/clients/{$client->id}/suspend", ['reason' => 'não pagou'])
            ->assertStatus(403);

        $this->assertTrue($client->fresh()->isActive());
    }

    public function test_super_admin_can_suspend_and_reactivate_a_client(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $owner = User::factory()->create();
        $client = Client::factory()->create(['owner_user_id' => $owner->id]);

        $this->actingAs($superAdmin)
            ->postJson("/api/clients/{$client->id}/suspend", ['reason' => 'pagamento em atraso'])
            ->assertOk()
            ->assertJsonFragment(['status' => 'suspended', 'suspended_reason' => 'pagamento em atraso']);

        $this->assertFalse($client->fresh()->isActive());

        $this->actingAs($superAdmin)
            ->postJson("/api/clients/{$client->id}/activate")
            ->assertOk()
            ->assertJsonFragment(['status' => 'active', 'suspended_reason' => null]);

        $this->assertTrue($client->fresh()->isActive());
    }

    public function test_suspended_client_loses_module_access_for_its_own_staff(): void
    {
        $owner = User::factory()->create();
        $staff = User::factory()->create();
        $client = Client::factory()->create([
            'owner_user_id' => $owner->id,
            'bot_installed_at' => now(),
            'status' => Client::STATUS_SUSPENDED,
        ]);
        $client->users()->attach($staff, ['role' => ClientUser::ROLE_STAFF]);
        $module = Module::factory()->create(['key' => 'members']);
        $client->modules()->attach($module, ['is_enabled' => true, 'payment_status' => 'active']);

        $this->actingAs($staff)
            ->getJson("/api/clients/{$client->id}/members")
            ->assertStatus(402);
    }

    public function test_super_admin_can_still_reach_a_suspended_clients_modules(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $owner = User::factory()->create();
        $client = Client::factory()->create([
            'owner_user_id' => $owner->id,
            'bot_installed_at' => now(),
            'status' => Client::STATUS_SUSPENDED,
        ]);
        $module = Module::factory()->create(['key' => 'members']);
        $client->modules()->attach($module, ['is_enabled' => true, 'payment_status' => 'active']);
        Member::factory()->create(['client_id' => $client->id]);

        $this->actingAs($superAdmin)
            ->getJson("/api/clients/{$client->id}/members")
            ->assertOk();
    }

    public function test_index_can_be_filtered_by_status(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $owner = User::factory()->create();
        Client::factory()->create(['owner_user_id' => $owner->id, 'status' => Client::STATUS_ACTIVE]);
        Client::factory()->create(['owner_user_id' => $owner->id, 'status' => Client::STATUS_SUSPENDED]);

        $response = $this->actingAs($superAdmin)->getJson('/api/clients?status=suspended')->assertOk();

        $this->assertCount(1, $response->json());
        $this->assertSame('suspended', $response->json()[0]['status']);
    }
}
