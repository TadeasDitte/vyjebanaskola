<?php

namespace Database\Factories;

use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Teacher>
 */
class TeacherFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'flair_id' => null,
            'short_code' => Str::upper(fake()->unique()->lexify('???')),
            'full_name' => fake()->name(),
            'note' => null,
        ];
    }
}
