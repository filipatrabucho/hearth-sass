<?php

namespace Database\Seeders;

use App\Domain\User\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * A pool of fake HearthGG dashboard users (client owners/admins/staff).
     * Your own account (created by actually logging in with Discord) isn't
     * seeded here - it becomes a super admin via SUPER_ADMIN_DISCORD_IDS
     * and can already see every client regardless of team membership.
     */
    public function run(): void
    {
        User::factory()->count(8)->create();
    }
}
