<?php

namespace Database\Seeders;

use App\Domain\Module\Module;
use Illuminate\Database\Seeder;

class ModuleSeeder extends Seeder
{
    /**
     * The initial module catalog. Enabling one for a client is a
     * separate, per-tenant decision (client_module.is_enabled).
     */
    public function run(): void
    {
        $modules = [
            ['key' => 'members', 'name' => 'Membros', 'description' => 'Listagem de membros da comunidade Discord.'],
            ['key' => 'events', 'name' => 'Eventos', 'description' => 'Criação e gestão de eventos da comunidade.'],
            ['key' => 'bans', 'name' => 'Bans', 'description' => 'Listagem de bans ativos na comunidade.'],
            ['key' => 'ban_appeals', 'name' => 'Pedidos de Unban', 'description' => 'Pedidos de utilizadores para levantar um ban.'],
            ['key' => 'tickets', 'name' => 'Tickets', 'description' => 'Sistema de tickets de suporte.'],
        ];

        foreach ($modules as $module) {
            Module::updateOrCreate(['key' => $module['key']], $module);
        }
    }
}
