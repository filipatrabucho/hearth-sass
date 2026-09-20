<?php

namespace Database\Seeders;

use App\Domain\Client\Client;
use App\Domain\Client\ClientUser;
use App\Domain\Invite\Invite;
use App\Domain\Member\Member;
use App\Domain\Module\Module;
use App\Domain\Post\Post;
use App\Domain\Ticket\Ticket;
use App\Domain\Ticket\TicketMessage;
use App\Domain\User\User;
use App\Domain\Warning\Warning;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;

/**
 * Four demo clients covering the states you'll actually run into: a fully
 * active one, a free client with only a couple of modules turned on, a
 * suspended one (billing gate), and one that hasn't connected the bot yet.
 */
class ClientSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();
        $modules = Module::all();

        $this->makeClient('Nightfall Gaming', 'pro', Client::STATUS_ACTIVE, botInstalled: true, users: $users, modules: $modules, populate: true);

        $this->makeClient('Aurora Community', 'free', Client::STATUS_ACTIVE, botInstalled: true, users: $users, modules: $modules, populate: true, moduleOverrides: [
            'events' => false,
            'posts' => false,
            'analytics' => false,
            'invites' => false,
        ]);

        $this->makeClient('Skybound Legion', 'pro', Client::STATUS_SUSPENDED, botInstalled: true, users: $users, modules: $modules, populate: true, suspendedReason: 'Pagamento em atraso');

        $this->makeClient('Retro Arcade', 'enterprise', Client::STATUS_ACTIVE, botInstalled: false, users: $users, modules: $modules, populate: false);
    }

    private function makeClient(
        string $name,
        string $plan,
        string $status,
        bool $botInstalled,
        Collection $users,
        Collection $modules,
        bool $populate,
        array $moduleOverrides = [],
        ?string $suspendedReason = null,
    ): Client {
        $owner = $users->random();

        $client = Client::factory()->create([
            'name' => $name,
            'plan' => $plan,
            'status' => $status,
            'suspended_reason' => $suspendedReason,
            'owner_user_id' => $owner->id,
            'bot_installed_at' => $botInstalled ? now() : null,
            'bot_permissions' => $botInstalled ? '8' : null,
        ]);

        $client->users()->attach($owner->id, ['role' => ClientUser::ROLE_OWNER]);

        foreach ($users->where('id', '!=', $owner->id)->random(2) as $teamMember) {
            $client->users()->attach($teamMember->id, [
                'role' => fake()->randomElement([ClientUser::ROLE_ADMIN, ClientUser::ROLE_STAFF]),
            ]);
        }

        foreach ($modules as $module) {
            $enabled = $moduleOverrides[$module->key] ?? true;

            $client->modules()->attach($module->id, [
                'is_enabled' => $enabled,
                'payment_status' => $enabled ? 'active' : 'trialing',
                'paid_until' => $enabled ? now()->addMonth() : null,
                'enabled_at' => $enabled ? now() : null,
            ]);
        }

        if ($populate) {
            $this->populateContent($client);
        }

        return $client;
    }

    private function populateContent(Client $client): void
    {
        Member::factory()->count(random_int(15, 30))->create(['client_id' => $client->id]);
        Invite::factory()->count(random_int(3, 6))->create(['client_id' => $client->id]);

        Warning::factory()->count(3)->create(['client_id' => $client->id]);
        Warning::factory()->resolved()->count(2)->create(['client_id' => $client->id]);

        Ticket::factory()->count(5)->create(['client_id' => $client->id])->each(function (Ticket $ticket) {
            TicketMessage::factory()->count(random_int(1, 4))->create(['ticket_id' => $ticket->id]);
        });

        Post::factory()->count(2)->create(['client_id' => $client->id]);
        Post::factory()->published()->count(3)->create(['client_id' => $client->id]);
    }
}
