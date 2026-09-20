<?php

namespace Tests\Feature;

use App\Domain\Client\Client;
use App\Domain\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeedDemoClientsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_client_per_plan_owned_by_the_super_admin(): void
    {
        $admin = User::factory()->create(['is_super_admin' => true]);

        $this->artisan('hearthgg:demo-clients')->assertSuccessful();

        $this->assertSame(3, Client::count());
        $this->assertDatabaseHas('clients', ['plan' => 'free', 'owner_user_id' => $admin->id]);
        $this->assertDatabaseHas('clients', ['plan' => 'pro', 'owner_user_id' => $admin->id]);
        $this->assertDatabaseHas('clients', ['plan' => 'enterprise', 'owner_user_id' => $admin->id]);

        $free = Client::where('plan', 'free')->first()->modules()->wherePivot('is_enabled', true)->pluck('key');
        $pro = Client::where('plan', 'pro')->first()->modules()->wherePivot('is_enabled', true)->pluck('key');

        $this->assertEqualsCanonicalizing(['members'], $free->all());
        $this->assertTrue($pro->contains('analytics'));
        $this->assertTrue($pro->count() > $free->count());
    }

    public function test_it_is_idempotent_and_targets_a_given_discord_id(): void
    {
        User::factory()->create(['is_super_admin' => true]);
        $other = User::factory()->create(['discord_id' => '42', 'is_super_admin' => true]);

        $this->artisan('hearthgg:demo-clients', ['discord_id' => '42'])->assertSuccessful();
        $this->artisan('hearthgg:demo-clients', ['discord_id' => '42'])->assertSuccessful();

        $this->assertSame(3, Client::count());
        $this->assertSame(0, Client::where('owner_user_id', '!=', $other->id)->count());
    }

    public function test_it_fails_gracefully_with_no_super_admin(): void
    {
        $this->artisan('hearthgg:demo-clients')->assertFailed();
        $this->assertSame(0, Client::count());
    }
}
