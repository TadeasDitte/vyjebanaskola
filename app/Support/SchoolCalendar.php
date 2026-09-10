<?php

namespace App\Support;

/**
 * Slovak school-year calendars.
 *
 * Dates come from the Ministry of Education's "Sprievodca školským rokom" (POP).
 * VERIFY against minedu.sk before each new school year and add / correct an entry
 * below — this is the single source of truth used by App\Services\HolidayService.
 *
 * A "breaks" entry:
 *   name           Slovak label shown to users
 *   start / end    inclusive ISO dates (school is closed on these days)
 *   region_groups  optional jarné-prázdniny groups (see Region::springBreakGroup);
 *                  omit for breaks that apply nationwide
 */
final class SchoolCalendar
{
    /**
     * All known school years, oldest first.
     *
     * @return non-empty-list<array{
     *     label: string,
     *     year_start: string,
     *     teaching_end: string,
     *     summer_holidays_start: string,
     *     breaks: list<array{name: string, start: string, end: string, region_groups?: list<int>}>
     * }>
     */
    public static function all(): array
    {
        return [
            [
                'label' => '2025/2026',
                'year_start' => '2025-09-02',
                'teaching_end' => '2026-06-30',
                'summer_holidays_start' => '2026-07-01',
                'breaks' => [
                    ['name' => 'Jesenné prázdniny', 'start' => '2025-10-30', 'end' => '2025-10-31'],
                    ['name' => 'Vianočné prázdniny', 'start' => '2025-12-22', 'end' => '2026-01-07'],
                    ['name' => 'Polročné prázdniny', 'start' => '2026-02-02', 'end' => '2026-02-02'],
                    ['name' => 'Jarné prázdniny', 'start' => '2026-02-16', 'end' => '2026-02-20', 'region_groups' => [1]],
                    ['name' => 'Jarné prázdniny', 'start' => '2026-02-23', 'end' => '2026-02-27', 'region_groups' => [2]],
                    ['name' => 'Jarné prázdniny', 'start' => '2026-03-02', 'end' => '2026-03-06', 'region_groups' => [3]],
                    ['name' => 'Veľkonočné prázdniny', 'start' => '2026-04-02', 'end' => '2026-04-07'],
                ],
            ],
            [
                'label' => '2026/2027',
                'year_start' => '2026-09-02',
                'teaching_end' => '2027-06-30',
                'summer_holidays_start' => '2027-07-01',
                'breaks' => [
                    ['name' => 'Jesenné prázdniny', 'start' => '2026-10-29', 'end' => '2026-10-30'],
                    ['name' => 'Vianočné prázdniny', 'start' => '2026-12-23', 'end' => '2027-01-07'],
                    ['name' => 'Polročné prázdniny', 'start' => '2027-02-01', 'end' => '2027-02-01'],
                    ['name' => 'Jarné prázdniny', 'start' => '2027-02-15', 'end' => '2027-02-19', 'region_groups' => [1]],
                    ['name' => 'Jarné prázdniny', 'start' => '2027-02-22', 'end' => '2027-02-26', 'region_groups' => [2]],
                    ['name' => 'Jarné prázdniny', 'start' => '2027-03-01', 'end' => '2027-03-05', 'region_groups' => [3]],
                    ['name' => 'Veľkonočné prázdniny', 'start' => '2027-03-25', 'end' => '2027-03-30'],
                ],
            ],
        ];
    }
}
