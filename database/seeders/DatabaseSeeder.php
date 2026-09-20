<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with demo data: log in with your
     * own Discord account (see README "Tornares-te super admin") to see
     * everything seeded here - you don't need to be added to any of it.
     */
    public function run(): void
    {
        $this->call([
            ModuleSeeder::class,
            UserSeeder::class,
            ClientSeeder::class,
            LeadSeeder::class,
        ]);
    }
}
