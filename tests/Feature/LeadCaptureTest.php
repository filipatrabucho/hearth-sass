<?php

namespace Tests\Feature;

use App\Domain\Lead\Lead;
use App\Domain\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadCaptureTest extends TestCase
{
    use RefreshDatabase;

    public function test_anyone_can_submit_a_lead_without_authenticating(): void
    {
        $response = $this->postJson('/api/leads', [
            'name' => 'Filipa',
            'email' => 'filipa@example.com',
            'discord_username' => 'filipa#0001',
            'server_name' => 'Nightfall Gaming',
            'plan_interest' => 'pro',
            'message' => 'We run a 5k member community.',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('leads', [
            'email' => 'filipa@example.com',
            'plan_interest' => 'pro',
            'status' => 'new',
            'source' => 'homepage',
        ]);
    }

    public function test_it_validates_required_fields(): void
    {
        $this->postJson('/api/leads', [])->assertStatus(422);
    }

    public function test_only_super_admins_can_list_leads(): void
    {
        Lead::create(['name' => 'A', 'email' => 'a@example.com']);
        $regular = User::factory()->create(['is_super_admin' => false]);
        $admin = User::factory()->create(['is_super_admin' => true]);

        $this->actingAs($regular)->getJson('/api/leads')->assertForbidden();
        $this->actingAs($admin)->getJson('/api/leads')->assertOk()->assertJsonCount(1);
    }

    public function test_super_admin_can_update_lead_status(): void
    {
        $lead = Lead::create(['name' => 'A', 'email' => 'a@example.com']);
        $admin = User::factory()->create(['is_super_admin' => true]);

        $this->actingAs($admin)
            ->putJson("/api/leads/{$lead->id}/status", ['status' => 'contacted'])
            ->assertOk()
            ->assertJsonFragment(['status' => 'contacted']);
    }
}
