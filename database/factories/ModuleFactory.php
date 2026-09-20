<?php

namespace Database\Factories;

use App\Domain\Module\Module;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Module>
 */
class ModuleFactory extends Factory
{
    protected $model = Module::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => fake()->unique()->slug(2, '_'),
            'name' => fake()->words(2, true),
            'description' => fake()->sentence(),
        ];
    }
}
