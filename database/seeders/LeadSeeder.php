<?php

namespace Database\Seeders;

use App\Domain\Lead\Lead;
use Illuminate\Database\Seeder;

class LeadSeeder extends Seeder
{
    /**
     * Spread across the last 14 days so the Leads page's sign-ups chart
     * has something to show, not just a spike "today".
     */
    public function run(): void
    {
        foreach (range(1, 18) as $i) {
            Lead::factory()->create([
                'status' => fake()->randomElement([
                    Lead::STATUS_NEW, Lead::STATUS_NEW, Lead::STATUS_NEW,
                    Lead::STATUS_CONTACTED, Lead::STATUS_CONTACTED,
                    Lead::STATUS_CONVERTED,
                    Lead::STATUS_ARCHIVED,
                ]),
                'created_at' => now()->subDays(random_int(0, 13))->subHours(random_int(0, 23)),
            ]);
        }
    }
}
