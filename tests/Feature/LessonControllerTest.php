<?php

namespace Tests\Feature;

use App\Models\Lesson;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LessonControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_reach_the_schedule()
    {
        $this->get('/schedule')->assertRedirect(route('login'));
    }

    public function test_a_user_can_create_a_lesson()
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/schedule', [
            'day_of_week' => 2,
            'period' => 3,
            'subject_code' => 'MAT',
            'week_parity' => 'odd',
        ])->assertRedirect();

        $this->assertDatabaseHas('lessons', [
            'user_id' => $user->id,
            'subject_code' => 'MAT',
            'week_parity' => 'odd',
        ]);
    }

    public function test_teacher_must_belong_to_the_user()
    {
        $user = User::factory()->create();
        $otherTeacher = Teacher::factory()->create();

        $this->actingAs($user)->post('/schedule', [
            'day_of_week' => 1,
            'period' => 0,
            'subject_code' => 'ANJ',
            'week_parity' => 'every',
            'teacher_id' => $otherTeacher->id,
        ])->assertSessionHasErrors('teacher_id');
    }

    public function test_a_user_cannot_modify_someone_elses_lesson()
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $lesson = Lesson::factory()->for($owner)->create();

        $this->actingAs($intruder)
            ->put("/schedule/{$lesson->id}", [
                'day_of_week' => 1,
                'period' => 1,
                'subject_code' => 'HACK',
                'week_parity' => 'every',
            ])
            ->assertForbidden();

        $this->actingAs($intruder)->delete("/schedule/{$lesson->id}")->assertForbidden();
        $this->assertDatabaseHas('lessons', ['id' => $lesson->id]);
    }
}
