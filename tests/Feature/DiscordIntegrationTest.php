<?php

namespace Tests\Feature;

use App\Domain\Client\Client;
use App\Domain\Client\ClientUser;
use App\Domain\Module\Module;
use App\Domain\Post\Post;
use App\Domain\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DiscordIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private function staffFor(Client $client): User
    {
        $staff = User::factory()->create();
        $client->users()->attach($staff, ['role' => ClientUser::ROLE_STAFF]);

        return $staff;
    }

    private function clientWithModule(string $key): Client
    {
        $owner = User::factory()->create();
        $client = Client::factory()->create(['owner_user_id' => $owner->id, 'bot_installed_at' => now()]);
        $module = Module::factory()->create(['key' => $key]);
        $client->modules()->attach($module, ['is_enabled' => true, 'payment_status' => 'active']);

        return $client;
    }

    public function test_opening_a_ticket_creates_a_private_discord_channel(): void
    {
        Http::fake([
            'discord.com/api/v10/guilds/*/channels' => Http::response(['id' => 'chan-123'], 200),
        ]);

        $client = $this->clientWithModule('tickets');
        $staff = $this->staffFor($client);

        $this->actingAs($staff)
            ->postJson("/api/clients/{$client->id}/tickets", [
                'discord_user_id' => '999',
                'subject' => 'Preciso de ajuda',
            ])
            ->assertCreated()
            ->assertJsonFragment(['discord_channel_id' => 'chan-123']);

        $this->assertDatabaseHas('tickets', ['client_id' => $client->id, 'discord_channel_id' => 'chan-123']);

        Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bot test-bot-token')
            && str_contains($request->url(), "/guilds/{$client->discord_guild_id}/channels"));
    }

    public function test_publishing_a_post_sends_a_discord_message_and_records_it(): void
    {
        Http::fake([
            'discord.com/api/v10/channels/*/messages' => Http::response(['id' => 'msg-456'], 200),
        ]);

        $client = $this->clientWithModule('posts');
        $staff = $this->staffFor($client);

        $post = Post::create([
            'client_id' => $client->id,
            'author_user_id' => $staff->id,
            'discord_channel_id' => 'announcements-channel',
            'title' => 'Novidades',
            'content' => 'Conteudo do anuncio.',
            'status' => Post::STATUS_DRAFT,
        ]);

        $this->actingAs($staff)
            ->postJson("/api/clients/{$client->id}/posts/{$post->id}/publish")
            ->assertOk()
            ->assertJsonFragment(['discord_message_id' => 'msg-456', 'status' => 'published']);

        $this->assertDatabaseHas('posts', ['id' => $post->id, 'discord_message_id' => 'msg-456']);
    }

    public function test_syncing_members_upserts_the_local_cache_from_discord(): void
    {
        $client = $this->clientWithModule('members');
        $staff = $this->staffFor($client);

        Http::fake([
            "discord.com/api/v10/guilds/{$client->discord_guild_id}/members*" => Http::sequence()
                ->push([
                    ['user' => ['id' => '1', 'username' => 'alice'], 'roles' => ['role-a']],
                ])
                ->push([]),
        ]);

        $this->actingAs($staff)
            ->postJson("/api/clients/{$client->id}/members/sync")
            ->assertOk()
            ->assertJson(['synced' => 1]);

        $this->assertDatabaseHas('members', [
            'client_id' => $client->id,
            'discord_user_id' => '1',
            'username' => 'alice',
        ]);
    }
}
