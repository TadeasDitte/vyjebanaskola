<?php

namespace App\Services;

use App\Enums\Region;
use App\Support\SchoolCalendar;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Slovak public holidays and school breaks.
 *
 * Public holidays ("dni pracovného pokoja") are computed from fixed rules plus
 * the movable Easter dates; school breaks come from App\Support\SchoolCalendar.
 * On every one of these days schools are closed.
 *
 * @phpstan-type YearConfig array{label: string, year_start: string, teaching_end: string, summer_holidays_start: string, breaks: list<array{name: string, start: string, end: string, region_groups?: list<int>}>}
 */
class HolidayService
{
    public const TYPE_PUBLIC_HOLIDAY = 'statny_sviatok';

    public const TYPE_SCHOOL_BREAK = 'prazdniny';

    /** @var array<int, Collection<int, array{date: CarbonImmutable, name: string}>> */
    private array $publicHolidayCache = [];

    /** @var array<string, Collection<int, array{name: string, start: CarbonImmutable, end: CarbonImmutable}>> */
    private array $schoolBreakCache = [];

    /**
     * Fixed-date Slovak public holidays as [month, day => name].
     *
     * @var array<string, string>
     */
    private const FIXED_HOLIDAYS = [
        '1-1' => 'Deň vzniku Slovenskej republiky',
        '1-6' => 'Zjavenie Pána (Traja králi)',
        '5-1' => 'Sviatok práce',
        '5-8' => 'Deň víťazstva nad fašizmom',
        '7-5' => 'Sviatok svätého Cyrila a Metoda',
        '8-29' => 'Výročie SNP',
        '9-1' => 'Deň Ústavy Slovenskej republiky',
        '9-15' => 'Sedembolestná Panna Mária',
        '11-1' => 'Sviatok všetkých svätých',
        '11-17' => 'Deň boja za slobodu a demokraciu',
        '12-24' => 'Štedrý deň',
        '12-25' => 'Prvý sviatok vianočný',
        '12-26' => 'Druhý sviatok vianočný',
    ];

    /**
     * Easter Sunday for a given year (Anonymous Gregorian / Meeus algorithm).
     */
    public function easterSunday(int $year): CarbonImmutable
    {
        $a = $year % 19;
        $b = intdiv($year, 100);
        $c = $year % 100;
        $d = intdiv($b, 4);
        $e = $b % 4;
        $f = intdiv($b + 8, 25);
        $g = intdiv($b - $f + 1, 3);
        $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = intdiv($c, 4);
        $k = $c % 4;
        $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
        $m = intdiv($a + 11 * $h + 22 * $l, 451);
        $month = intdiv($h + $l - 7 * $m + 114, 31);
        $day = (($h + $l - 7 * $m + 114) % 31) + 1;

        return CarbonImmutable::create($year, $month, $day)->startOfDay();
    }

    /**
     * All Slovak public holidays in a calendar year, ordered by date.
     *
     * @return Collection<int, array{date: CarbonImmutable, name: string}>
     */
    public function publicHolidays(int $year): Collection
    {
        return $this->publicHolidayCache[$year] ??= $this->computePublicHolidays($year);
    }

    /**
     * @return Collection<int, array{date: CarbonImmutable, name: string}>
     */
    private function computePublicHolidays(int $year): Collection
    {
        $easter = $this->easterSunday($year);

        /** @var Collection<int, array{date: CarbonImmutable, name: string}> $holidays */
        $holidays = collect(self::FIXED_HOLIDAYS)
            ->map(function (string $name, string $key) use ($year): array {
                [$month, $day] = array_map('intval', explode('-', $key));

                return [
                    'date' => CarbonImmutable::create($year, $month, $day)->startOfDay(),
                    'name' => $name,
                ];
            })
            ->values();

        $holidays->push(['date' => $easter->subDays(2), 'name' => 'Veľký piatok']);
        $holidays->push(['date' => $easter->addDay(), 'name' => 'Veľkonočný pondelok']);

        return $holidays->sortBy(fn (array $h) => $h['date']->timestamp)->values();
    }

    public function isPublicHoliday(CarbonInterface $date): bool
    {
        return $this->publicHolidays((int) $date->year)
            ->contains(fn (array $h) => $h['date']->isSameDay($date));
    }

    /**
     * The school-year config that applies to a date: the year that contains it,
     * otherwise the next upcoming year, otherwise the most recent one.
     *
     * @return YearConfig
     */
    private function yearFor(CarbonInterface $date): array
    {
        $date = CarbonImmutable::parse($date)->startOfDay();
        $years = SchoolCalendar::all();

        foreach ($years as $year) {
            $start = CarbonImmutable::parse($year['year_start']);
            $summer = CarbonImmutable::parse($year['summer_holidays_start']);

            if ($date->betweenIncluded($start, $summer)) {
                return $year;
            }
        }

        foreach ($years as $year) {
            if ($date->lt(CarbonImmutable::parse($year['year_start']))) {
                return $year;
            }
        }

        return $years[array_key_last($years)];
    }

    /**
     * @return array{start: CarbonImmutable, teaching_end: CarbonImmutable, summer_start: CarbonImmutable}
     */
    public function schoolYearBounds(?CarbonInterface $reference = null): array
    {
        $year = $this->yearFor($reference ?? CarbonImmutable::now());

        return [
            'start' => CarbonImmutable::parse($year['year_start'])->startOfDay(),
            'teaching_end' => CarbonImmutable::parse($year['teaching_end'])->startOfDay(),
            'summer_start' => CarbonImmutable::parse($year['summer_holidays_start'])->startOfDay(),
        ];
    }

    /**
     * School breaks for the given region and school year, ordered by start date.
     *
     * @return Collection<int, array{name: string, start: CarbonImmutable, end: CarbonImmutable}>
     */
    public function schoolBreaks(Region $region, ?CarbonInterface $reference = null): Collection
    {
        $year = $this->yearFor($reference ?? CarbonImmutable::now());
        $key = $region->value.'|'.$year['label'];

        return $this->schoolBreakCache[$key] ??= $this->computeSchoolBreaks($region, $year);
    }

    /**
     * @param  YearConfig  $year
     * @return Collection<int, array{name: string, start: CarbonImmutable, end: CarbonImmutable}>
     */
    private function computeSchoolBreaks(Region $region, array $year): Collection
    {
        $group = $region->springBreakGroup();

        return collect($year['breaks'])
            ->filter(function (array $break) use ($group): bool {
                $groups = $break['region_groups'] ?? null;

                return $groups === null || in_array($group, $groups, true);
            })
            ->map(fn (array $break): array => [
                'name' => $break['name'],
                'start' => CarbonImmutable::parse($break['start'])->startOfDay(),
                'end' => CarbonImmutable::parse($break['end'])->startOfDay(),
            ])
            ->sortBy(fn (array $b) => $b['start']->timestamp)
            ->values();
    }

    public function isWithinSchoolBreak(CarbonInterface $date, Region $region): bool
    {
        return $this->schoolBreaks($region, $date)->contains(
            fn (array $break) => $date->betweenIncluded($break['start'], $break['end']),
        );
    }

    /**
     * Whether pupils in the given region attend school on this date: a weekday
     * inside its school year that is neither a public holiday nor a break.
     */
    public function isSchoolDay(CarbonInterface $date, Region $region): bool
    {
        $bounds = $this->schoolYearBounds($date);

        return $date->isWeekday()
            && $date->betweenIncluded($bounds['start'], $bounds['teaching_end'])
            && ! $this->isPublicHoliday($date)
            && ! $this->isWithinSchoolBreak($date, $region);
    }

    /**
     * Number of school days for the region within an inclusive date range.
     */
    public function schoolDaysBetween(Region $region, CarbonInterface $from, CarbonInterface $to): int
    {
        $from = CarbonImmutable::parse($from)->startOfDay();
        $to = CarbonImmutable::parse($to)->startOfDay();

        $count = 0;

        for ($date = $from; $date->lessThanOrEqualTo($to); $date = $date->addDay()) {
            if ($this->isSchoolDay($date, $region)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Upcoming days off (public holidays and school breaks), merged and sorted.
     *
     * @return Collection<int, array{
     *     name: string, type: string, start: CarbonImmutable, end: CarbonImmutable,
     *     days_until: int, ongoing: bool
     * }>
     */
    public function nearestHolidays(Region $region, ?CarbonInterface $from = null, int $limit = 6): Collection
    {
        $from = ($from ? CarbonImmutable::parse($from) : CarbonImmutable::now())->startOfDay();
        $horizon = $from->addYear();
        $group = $region->springBreakGroup();

        /** @var list<array{name: string, type: string, start: CarbonImmutable, end: CarbonImmutable}> $items */
        $items = [];

        foreach ([$from->year, $from->year + 1] as $year) {
            foreach ($this->publicHolidays($year) as $holiday) {
                if ($holiday['date']->lt($from) || $holiday['date']->gt($horizon)) {
                    continue;
                }

                $items[] = [
                    'name' => $holiday['name'],
                    'type' => self::TYPE_PUBLIC_HOLIDAY,
                    'start' => $holiday['date'],
                    'end' => $holiday['date'],
                ];
            }
        }

        foreach (SchoolCalendar::all() as $year) {
            foreach ($year['breaks'] as $break) {
                $groups = $break['region_groups'] ?? null;

                if ($groups !== null && ! in_array($group, $groups, true)) {
                    continue;
                }

                $start = CarbonImmutable::parse($break['start'])->startOfDay();
                $end = CarbonImmutable::parse($break['end'])->startOfDay();

                if ($end->lt($from) || $start->gt($horizon)) {
                    continue;
                }

                $items[] = [
                    'name' => $break['name'],
                    'type' => self::TYPE_SCHOOL_BREAK,
                    'start' => $start,
                    'end' => $end,
                ];
            }
        }

        usort($items, fn (array $a, array $b): int => $a['start']->timestamp <=> $b['start']->timestamp);

        return collect(array_slice($items, 0, $limit))->map(function (array $item) use ($from): array {
            $ongoing = $from->betweenIncluded($item['start'], $item['end']);

            return [
                'name' => $item['name'],
                'type' => $item['type'],
                'start' => $item['start'],
                'end' => $item['end'],
                'days_until' => $ongoing ? 0 : (int) $from->diffInDays($item['start']),
                'ongoing' => $ongoing,
            ];
        });
    }
}
