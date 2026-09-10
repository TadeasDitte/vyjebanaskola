<?php

namespace Database\Factories;

use App\Enums\WeekParity;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Lesson>
 */
class LessonFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'teacher_id' => null,
            'day_of_week' => fake()->numberBetween(1, 5),
            'period' => fake()->numberBetween(0, 8),
            'subject_code' => Str::upper(fake()->lexify('???')),
            'subject_name' => fake()->words(2, true),
            'room' => fake()->bothify('?-###'),
            'group_label' => fake()->randomElement([null, 'S1', 'L1']),
            'week_parity' => WeekParity::Every,
        ];
    }
}
