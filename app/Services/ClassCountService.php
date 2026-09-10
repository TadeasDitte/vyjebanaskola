<?php

namespace App\Services;

use App\Enums\WeekParity;
use App\Models\Lesson;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Projects a user's recurring weekly timetable across the remaining school year
 * and answers "how many classes are left with each teacher / each flair".
 */
class ClassCountService
{
    public function __construct(private readonly HolidayService $holidays) {}

    /**
     * Week parity for a date relative to the user's anchor. The anchor date is
     * declared by the user to sit in a "nepárny" (odd) week. Without an anchor we
     * treat every week as odd, so only `every` and `odd` lessons are ever counted.
     */
    public function weekParityFor(CarbonInterface $date, User $user): WeekParity
    {
        if ($user->week_parity_anchor === null) {
            return WeekParity::Odd;
        }

        $anchorWeek = CarbonImmutable::parse($user->week_parity_anchor)->startOfWeek();
        $dateWeek = CarbonImmutable::parse($date)->startOfWeek();
        $weeks = intdiv((int) round(abs($anchorWeek->diffInDays($dateWeek))), 7);

        return $weeks % 2 === 0 ? WeekParity::Odd : WeekParity::Even;
    }

    /**
     * @return array{
     *     has_region: bool,
     *     range_start: string|null,
     *     range_end: string|null,
     *     school_days_remaining: int,
     *     total_remaining: int,
     *     next_school_day: string|null,
     *     per_teacher: list<array{teacher_id: int|null, short_code: string, full_name: string|null, flair: array{id: int, label: string, color: string, sentiment: int}|null, remaining: int, next_date: string|null}>,
     *     per_flair: list<array{flair_id: int|null, label: string, color: string, sentiment: int, remaining: int}>,
     * }
     */
    public function projection(User $user, ?CarbonInterface $from = null): array
    {
        $region = $user->region;

        $from = ($from ? CarbonImmutable::parse($from) : CarbonImmutable::now())->startOfDay();
        $bounds = $this->holidays->schoolYearBounds($from);
        $cursor = $from->greaterThan($bounds['start']) ? $from : $bounds['start'];
        $end = $bounds['teaching_end'];

        $empty = [
            'has_region' => $region !== null,
            'range_start' => null,
            'range_end' => null,
            'school_days_remaining' => 0,
            'total_remaining' => 0,
            'next_school_day' => null,
            'per_teacher' => [],
            'per_flair' => [],
        ];

        if ($region === null || $cursor->greaterThan($end)) {
            return $empty;
        }

        /** @var Collection<int, Lesson> $lessons */
        $lessons = $user->lessons()->with('teacher.flair')->get();
        $lessonsByDay = $lessons->groupBy('day_of_week');

        $schoolDaysRemaining = 0;
        $totalRemaining = 0;
        $nextSchoolDay = null;

        // teacher_id (or 0 for "no teacher") => ['remaining' => int, 'next' => ?CarbonImmutable]
        $counts = [];

        for ($date = $cursor; $date->lessThanOrEqualTo($end); $date = $date->addDay()) {
            if (! $this->holidays->isSchoolDay($date, $region)) {
                continue;
            }

            $schoolDaysRemaining++;
            $nextSchoolDay ??= $date;

            $parity = $this->weekParityFor($date, $user);

            foreach ($lessonsByDay->get($date->dayOfWeekIso, collect()) as $lesson) {
                if (! $lesson->week_parity->matches($parity)) {
                    continue;
                }

                $totalRemaining++;
                $key = $lesson->teacher_id ?? 0;
                $counts[$key]['remaining'] = ($counts[$key]['remaining'] ?? 0) + 1;
                $counts[$key]['next'] ??= $date;
            }
        }

        return [
            'has_region' => true,
            'range_start' => $cursor->toDateString(),
            'range_end' => $end->toDateString(),
            'school_days_remaining' => $schoolDaysRemaining,
            'total_remaining' => $totalRemaining,
            'next_school_day' => $nextSchoolDay?->toDateString(),
            'per_teacher' => $this->buildPerTeacher($user, $counts),
            'per_flair' => $this->buildPerFlair($user, $counts),
        ];
    }

    /**
     * @param  array<int, array{remaining?: int, next?: CarbonImmutable}>  $counts
     * @return list<array{teacher_id: int|null, short_code: string, full_name: string|null, flair: array{id: int, label: string, color: string, sentiment: int}|null, remaining: int, next_date: string|null}>
     */
    private function buildPerTeacher(User $user, array $counts): array
    {
        $rows = [];

        foreach ($user->teachers()->with('flair')->get() as $teacher) {
            $count = $counts[$teacher->id] ?? [];
            $flair = $teacher->flair;

            $rows[] = [
                'teacher_id' => $teacher->id,
                'short_code' => $teacher->short_code,
                'full_name' => $teacher->full_name,
                'flair' => $flair === null ? null : [
                    'id' => $flair->id,
                    'label' => $flair->label,
                    'color' => $flair->color,
                    'sentiment' => $flair->sentiment,
                ],
                'remaining' => $count['remaining'] ?? 0,
                'next_date' => isset($count['next']) ? $count['next']->toDateString() : null,
            ];
        }

        if (isset($counts[0])) {
            $rows[] = [
                'teacher_id' => null,
                'short_code' => '—',
                'full_name' => 'Bez učiteľa',
                'flair' => null,
                'remaining' => $counts[0]['remaining'] ?? 0,
                'next_date' => isset($counts[0]['next']) ? $counts[0]['next']->toDateString() : null,
            ];
        }

        usort($rows, fn (array $a, array $b): int => $b['remaining'] <=> $a['remaining']);

        return $rows;
    }

    /**
     * @param  array<int, array{remaining?: int, next?: CarbonImmutable}>  $counts
     * @return list<array{flair_id: int|null, label: string, color: string, sentiment: int, remaining: int}>
     */
    private function buildPerFlair(User $user, array $counts): array
    {
        $teachers = $user->teachers()->with('flair')->get()->keyBy('id');

        /** @var array<int, array{flair_id: int, label: string, color: string, sentiment: int, remaining: int}> $buckets */
        $buckets = [];
        $unrated = 0;

        foreach ($counts as $teacherId => $count) {
            $remaining = $count['remaining'] ?? 0;
            $flair = $teacherId === 0 ? null : $teachers->get($teacherId)?->flair;

            if ($flair === null) {
                $unrated += $remaining;

                continue;
            }

            $buckets[$flair->id] ??= [
                'flair_id' => $flair->id,
                'label' => $flair->label,
                'color' => $flair->color,
                'sentiment' => $flair->sentiment,
                'remaining' => 0,
            ];
            $buckets[$flair->id]['remaining'] += $remaining;
        }

        $rows = array_values($buckets);
        usort(
            $rows,
            fn (array $a, array $b): int => $b['sentiment'] <=> $a['sentiment'] ?: strcmp($a['label'], $b['label']),
        );

        if ($unrated > 0) {
            $rows[] = [
                'flair_id' => null,
                'label' => 'Bez hodnotenia',
                'color' => '#94a3b8',
                'sentiment' => 0,
                'remaining' => $unrated,
            ];
        }

        return $rows;
    }
}
