<?php

namespace App\Console\Commands;

use App\Domain\Client\Client;
use App\Domain\Module\Module;
use App\Domain\User\User;
use Database\Seeders\ModuleSeeder;
use Illuminate\Console\Command;

/**
 * Creates (or resets) three demo clients - one per plan - all owned by a
 * super admin, so the dashboard, module list, and upgrade prompts can be
 * compared side by side just by switching clients in the app itself. Safe
 * to re-run: it upserts by a fixed discord_guild_id per tier.
 */
class SeedDemoClients extends Command
{
    protected $signature = 'hearthgg:demo-clients {discord_id? : Owner\'s Discord ID (defaults to the first super admin found)}';

    protected $description = 'Seed a Free/Pro/Enterprise demo client so you can compare each plan\'s view';

    /**
     * @var array<string, array{name: string, guild_id: string, modules: array<int, string>}>
     */
    private const TIERS = [
        'free' => [
            'name' => 'HearthGG Demo · Free',
            'guild_id' => '900000000000000001',
            'modules' => ['members'],
        ],
        'pro' => [
            'name' => 'HearthGG Demo · Pro',
            'guild_id' => '900000000000000002',
            'modules' => ['members', 'bans', 'events', 'tickets', 'posts', 'analytics'],
        ],
        'enterprise' => [
            'name' => 'HearthGG Demo · Enterprise',
            'guild_id' => '900000000000000003',
            'modules' => ['members', 'bans', 'events', 'tickets', 'posts', 'analytics'],
        ],
    ];

    public function handle(): int
    {
        $owner = $this->resolveOwner();

        if (! $owner) {
            $this->error('No super admin found. Log in with Discord once, add your Discord ID to SUPER_ADMIN_DISCORD_IDS, and log in again - then re-run this command.');

            return self::FAILURE;
        }

        $this->call('db:seed', ['--class' => ModuleSeeder::class]);
        $catalog = Module::all();

        if ($catalog->isEmpty()) {
            $this->error('Module catalog is empty even after seeding - something is wrong with ModuleSeeder.');

            return self::FAILURE;
        }

        foreach (self::TIERS as $plan => $tier) {
            $client = Client::updateOrCreate(
                ['discord_guild_id' => $tier['guild_id']],
                [
                    'name' => $tier['name'],
                    'owner_user_id' => $owner->id,
                    'plan' => $plan,
                    'status' => Client::STATUS_ACTIVE,
                ]
            );

            $pivots = [];
            foreach ($catalog as $module) {
                $enabled = in_array($module->key, $tier['modules'], true);
                $pivots[$module->id] = [
                    'is_enabled' => $enabled,
                    'payment_status' => $enabled ? 'active' : 'canceled',
                    'paid_until' => null,
                    'enabled_at' => $enabled ? now() : null,
                ];
            }
            $client->modules()->sync($pivots);

            $this->info("[{$plan}] {$client->name} (id {$client->id}) - modules: ".implode(', ', $tier['modules']));
        }

        $this->newLine();
        $this->info("Owner: {$owner->username} (#{$owner->id}). Switch clients from the dashboard's client switcher to compare views.");

        return self::SUCCESS;
    }

    private function resolveOwner(): ?User
    {
        $discordId = $this->argument('discord_id');

        if ($discordId) {
            return User::where('discord_id', $discordId)->first();
        }

        return User::where('is_super_admin', true)->first();
    }
}
