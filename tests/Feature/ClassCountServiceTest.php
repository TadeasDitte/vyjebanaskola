<?php

namespace Tests\Feature;

use App\Enums\Region;
use App\Enums\WeekParity;
use App\Models\Flair;
use App\Models\Lesson;
use App\Models\Teacher;
use App\Models\User;
use App\Services\ClassCountService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClassCountServiceTest extends TestCase
{
    use RefreshDatabase;

    private ClassCountService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ClassCountService::class);
    }

    public function test_projection_is_empty_without_a_region()
    {
        $user = User::factory()->create(['region' => null]);
        Lesson::factory()->for($user)->create(['day_of_week' => 1, 'week_parity' => WeekParity::Every]);

        $result = $this->service->projection($user, CarbonImmutable::parse('2026-06-01'));

        $this->assertFalse($result['has_region']);
        $this->assertSame(0, $result['total_remaining']);
        $this->assertSame([], $result['per_teacher']);
    }

    public function test_counts_every_remaining_occurrence_of_a_weekly_lesson()
    {
        $user = User::factory()->create(['region' => Region::Kosicky, 'week_parity_anchor' => null]);
        Lesson::factory()->for($user)->create(['day_of_week' => 1, 'week_parity' => WeekParity::Every]);

        // From 1 June 2026 to teaching_end 30 June 2026 there are 5 Mondays, all school days.
        $result = $this->service->projection($user, CarbonImmutable::parse('2026-06-01'));

        $this->assertTrue($result['has_region']);
        $this->assertSame(5, $result['total_remaining']);
        $this->assertSame(22, $result['school_days_remaining']);
        $this->assertSame('2026-06-01', $result['next_school_day']);

        // No teacher assigned -> lands in the unrated flair bucket and a null-teacher row.
        $this->assertSame(1, count($result['per_flair']));
        $this->assertNull($result['per_flair'][0]['flair_id']);
        $this->assertSame(5, $result['per_flair'][0]['remaining']);
        $this->assertNull($result['per_teacher'][0]['teacher_id']);
    }

    public function test_week_parity_limits_which_weeks_are_counted()
    {
        $user = User::factory()->create([
            'region' => Region::Kosicky,
            'week_parity_anchor' => '2026-06-01', // this week is "odd"
        ]);
        Lesson::factory()->for($user)->create(['day_of_week' => 1, 'week_parity' => WeekParity::Odd]);
        Lesson::factory()->for($user)->create(['day_of_week' => 1, 'week_parity' => WeekParity::Even]);

        $result = $this->service->projection($user, CarbonImmutable::parse('2026-06-01'));

        // Odd Mondays: 1, 15, 29 June. Even Mondays: 8, 22 June.
        $this->assertSame(5, $result['total_remaining']);
        $perParity = collect($result['per_teacher'])->firstWhere('teacher_id', null);
        $this->assertSame(5, $perParity['remaining']);

        $oddOnly = User::factory()->create([
            'region' => Region::Kosicky,
            'week_parity_anchor' => '2026-06-01',
        ]);
        Lesson::factory()->for($oddOnly)->create(['day_of_week' => 1, 'week_parity' => WeekParity::Odd]);
        $this->assertSame(3, $this->service->projection($oddOnly, CarbonImmutable::parse('2026-06-01'))['total_remaining']);
    }

    public function test_region_specific_spring_break_changes_remaining_school_days()
    {
        $from = CarbonImmutable::parse('2026-02-24');

        $bratislava = User::factory()->create(['region' => Region::Bratislavsky]);
        $kosice = User::factory()->create(['region' => Region::Kosicky]);

        $baDays = $this->service->projection($bratislava, $from)['school_days_remaining'];
        $keDays = $this->service->projection($kosice, $from)['school_days_remaining'];

        // On 24 Feb, Bratislava's spring break is already over but Košice's (2–6 Mar)
        // still lies ahead, removing five school days.
        $this->assertSame($baDays, $keDays + 5);
    }

    public function test_flair_buckets_group_teacher_counts_by_sentiment()
    {
        $user = User::factory()->create(['region' => Region::Kosicky, 'week_parity_anchor' => null]);
        $good = Flair::factory()->for($user)->create(['label' => 'dobrá', 'sentiment' => 1]);
        $bad = Flair::factory()->for($user)->create(['label' => 'zlá', 'sentiment' => -1]);

        $niceTeacher = Teacher::factory()->for($user)->create(['flair_id' => $good->id]);
        $meanTeacher = Teacher::factory()->for($user)->create(['flair_id' => $bad->id]);

        Lesson::factory()->for($user)->create(['day_of_week' => 1, 'teacher_id' => $niceTeacher->id, 'week_parity' => WeekParity::Every]);
        Lesson::factory()->for($user)->create(['day_of_week' => 2, 'teacher_id' => $meanTeacher->id, 'week_parity' => WeekParity::Every]);

        $result = $this->service->projection($user, CarbonImmutable::parse('2026-06-01'));

        // Good sentiment sorts first.
        $this->assertSame('dobrá', $result['per_flair'][0]['label']);
        $this->assertSame(5, $result['per_flair'][0]['remaining']); // 5 Mondays
        $this->assertSame('zlá', $result['per_flair'][1]['label']);
        $this->assertSame(5, $result['per_flair'][1]['remaining']); // Tuesdays: 2, 9, 16, 23, 30 June
    }
}
