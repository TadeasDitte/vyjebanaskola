<?php

namespace Tests\Feature;

use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_visiting_the_teachers_page_seeds_default_flairs_once()
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/teachers')->assertOk();
        $this->assertSame(3, $user->flairs()->count());

        $this->actingAs($user)->get('/teachers')->assertOk();
        $this->assertSame(3, $user->flairs()->count());
    }

    public function test_short_code_is_unique_per_user_but_not_globally()
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();

        $this->actingAs($alice)->post('/teachers', ['short_code' => 'SIE'])->assertRedirect();
        $this->actingAs($alice)->post('/teachers', ['short_code' => 'SIE'])
            ->assertSessionHasErrors('short_code');

        $this->actingAs($bob)->post('/teachers', ['short_code' => 'SIE'])->assertRedirect();

        $this->assertDatabaseCount('teachers', 2);
    }

    public function test_a_user_cannot_modify_someone_elses_teacher()
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $teacher = Teacher::factory()->for($owner)->create(['short_code' => 'ABC']);

        $this->actingAs($intruder)
            ->put("/teachers/{$teacher->id}", ['short_code' => 'XYZ'])
            ->assertForbidden();

        $this->actingAs($intruder)->delete("/teachers/{$teacher->id}")->assertForbidden();
        $this->assertDatabaseHas('teachers', ['id' => $teacher->id, 'short_code' => 'ABC']);
    }
}
