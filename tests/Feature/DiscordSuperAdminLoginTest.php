<?php

namespace Tests\Feature;

use App\Domain\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class DiscordSuperAdminLoginTest extends TestCase
{
    use RefreshDatabase;

    private function fakeDiscordLogin(string $discordId): void
    {
        $socialiteUser = (new SocialiteUser)->setRaw([
            'id' => $discordId,
            'username' => 'filipa',
            'discriminator' => '0',
            'global_name' => 'Filipa',
            'avatar' => null,
        ]);
        $socialiteUser->id = $discordId;
        $socialiteUser->nickname = 'filipa';
        $socialiteUser->email = 'filipa@example.com';
        $socialiteUser->token = 'fake-token';
        $socialiteUser->refreshToken = 'fake-refresh';
        $socialiteUser->expiresIn = 3600;

        $provider = Mockery::mock();
        $provider->shouldReceive('user')->andReturn($socialiteUser);

        Socialite::shouldReceive('driver')->with('discord')->andReturn($provider);
    }

    public function test_logging_in_with_a_listed_discord_id_becomes_super_admin(): void
    {
        config(['services.discord.super_admin_ids' => ['111']]);
        $this->fakeDiscordLogin('111');

        $this->get('/auth/discord/callback')->assertRedirect();

        $this->assertDatabaseHas('users', ['discord_id' => '111', 'is_super_admin' => true]);
    }

    public function test_logging_in_with_an_unlisted_discord_id_stays_a_regular_user(): void
    {
        config(['services.discord.super_admin_ids' => ['111']]);
        $this->fakeDiscordLogin('222');

        $this->get('/auth/discord/callback')->assertRedirect();

        $this->assertDatabaseHas('users', ['discord_id' => '222', 'is_super_admin' => false]);
    }

    public function test_removing_an_id_from_the_env_list_does_not_demote_an_existing_admin(): void
    {
        User::factory()->create(['discord_id' => '111', 'is_super_admin' => true]);
        config(['services.discord.super_admin_ids' => []]);
        $this->fakeDiscordLogin('111');

        $this->get('/auth/discord/callback')->assertRedirect();

        $this->assertDatabaseHas('users', ['discord_id' => '111', 'is_super_admin' => true]);
    }
}
