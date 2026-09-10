<?php

namespace Database\Factories;

use App\Models\Flair;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Flair>
 */
class FlairFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'label' => fake()->unique()->word(),
            'color' => fake()->hexColor(),
            'sentiment' => fake()->randomElement([-1, 0, 1]),
            'position' => 0,
        ];
    }
}
