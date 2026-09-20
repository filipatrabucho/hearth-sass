<?php

namespace Tests\Feature;

use App\Domain\Lead\Lead;
use App\Domain\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_anyone_can_submit_the_public_get_started_form(): void
    {
        $this->postJson('/api/leads', [
            'name' => 'Filipa',
            'email' => 'filipa@example.com',
            'discord_username' => 'filipa#0',
            'server_name' => 'Nightfall Gaming',
            'plan_interest' => 'pro',
            'message' => 'We have ~500 members.',
        ])
            ->assertCreated()
            ->assertJsonFragment(['status' => 'new', 'source' => 'homepage']);

        $this->assertDatabaseHas('leads', ['email' => 'filipa@example.com', 'plan_interest' => 'pro']);
    }

    public function test_a_lead_cannot_spoof_its_own_status_or_source(): void
    {
        $response = $this->postJson('/api/leads', [
            'name' => 'Filipa',
            'email' => 'filipa@example.com',
            'plan_interest' => 'free',
            'status' => 'converted',
            'source' => 'not-homepage',
        ])->assertCreated();

        $this->assertSame('new', $response->json('status'));
        $this->assertSame('homepage', $response->json('source'));
    }

    public function test_guests_cannot_list_leads(): void
    {
        $this->getJson('/api/leads')->assertStatus(401);
    }

    public function test_a_regular_user_cannot_list_leads(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->getJson('/api/leads')->assertStatus(403);
    }

    public function test_super_admin_can_list_filter_and_manage_leads(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        Lead::factory()->create(['status' => Lead::STATUS_NEW]);
        $contacted = Lead::factory()->create(['status' => Lead::STATUS_CONTACTED]);

        $this->actingAs($superAdmin)
            ->getJson('/api/leads?status=contacted')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonFragment(['id' => $contacted->id]);

        $this->actingAs($superAdmin)
            ->putJson("/api/leads/{$contacted->id}/status", ['status' => 'converted'])
            ->assertOk()
            ->assertJsonFragment(['status' => 'converted']);

        $this->actingAs($superAdmin)
            ->deleteJson("/api/leads/{$contacted->id}")
            ->assertStatus(204);

        $this->assertDatabaseMissing('leads', ['id' => $contacted->id]);
    }
}
