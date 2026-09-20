<?php

namespace Database\Seeders;

use App\Domain\Module\Module;
use Illuminate\Database\Seeder;

class ModuleSeeder extends Seeder
{
    /**
     * The initial module catalog. Enabling one for a client is a
     * separate, per-tenant decision (client_module.is_enabled).
     *
     * Keys match the frontend's ModuleKey union (src/types/index.ts in
     * hearth-sass-frontend) exactly - the frontend nav gates the Invites
     * page on the 'members' module rather than its own, but still lists
     * 'invites' as its own row in the billing/catalog view.
     */
    public function run(): void
    {
        $modules = [
            ['key' => 'members', 'name' => 'Membros', 'description' => 'Listagem de membros da comunidade Discord.'],
            ['key' => 'events', 'name' => 'Eventos', 'description' => 'Criação e gestão de eventos da comunidade.'],
            ['key' => 'bans', 'name' => 'Bans', 'description' => 'Listagem de bans ativos na comunidade.'],
            ['key' => 'tickets', 'name' => 'Tickets', 'description' => 'Sistema de tickets de suporte.'],
            ['key' => 'posts', 'name' => 'Publicações', 'description' => 'Anúncios publicados num canal do Discord.'],
            ['key' => 'invites', 'name' => 'Convites', 'description' => 'Listagem de convites ativos da comunidade.'],
            ['key' => 'analytics', 'name' => 'Analytics', 'description' => 'Estatísticas da comunidade e audit log do Discord.'],
        ];

        foreach ($modules as $module) {
            Module::updateOrCreate(['key' => $module['key']], $module);
        }
    }
}
