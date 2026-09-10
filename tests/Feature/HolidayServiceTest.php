<?php

namespace Tests\Feature;

use App\Enums\Region;
use App\Services\HolidayService;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class HolidayServiceTest extends TestCase
{
    private HolidayService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new HolidayService;
    }

    public function test_easter_sunday_is_computed_correctly()
    {
        $this->assertTrue($this->service->easterSunday(2026)->isSameDay(CarbonImmutable::parse('2026-04-05')));
        $this->assertTrue($this->service->easterSunday(2025)->isSameDay(CarbonImmutable::parse('2025-04-20')));
        $this->assertTrue($this->service->easterSunday(2024)->isSameDay(CarbonImmutable::parse('2024-03-31')));
    }

    public function test_public_holidays_include_fixed_and_easter_derived_days()
    {
        $names = $this->service->publicHolidays(2026)->pluck('name');

        $this->assertContains('Deň vzniku Slovenskej republiky', $names);
        $this->assertContains('Sedembolestná Panna Mária', $names);
        $this->assertContains('Veľký piatok', $names);
        $this->assertContains('Veľkonočný pondelok', $names);
        $this->assertCount(15, $this->service->publicHolidays(2026));

        $this->assertTrue($this->service->isPublicHoliday(CarbonImmutable::parse('2026-04-03'))); // Veľký piatok
        $this->assertFalse($this->service->isPublicHoliday(CarbonImmutable::parse('2026-04-01')));
    }

    public function test_is_school_day_excludes_weekends_holidays_and_breaks()
    {
        $region = Region::Kosicky;

        $this->assertTrue($this->service->isSchoolDay(CarbonImmutable::parse('2026-01-13'), $region)); // Tuesday
        $this->assertFalse($this->service->isSchoolDay(CarbonImmutable::parse('2026-01-17'), $region)); // Saturday
        $this->assertFalse($this->service->isSchoolDay(CarbonImmutable::parse('2025-11-17'), $region)); // public holiday
        $this->assertFalse($this->service->isSchoolDay(CarbonImmutable::parse('2025-12-29'), $region)); // vianočné prázdniny
        $this->assertFalse($this->service->isSchoolDay(CarbonImmutable::parse('2026-07-10'), $region)); // after teaching_end
    }

    public function test_spring_break_depends_on_region()
    {
        $feb18 = CarbonImmutable::parse('2026-02-18');

        // Group 1 regions are off; group 3 regions still have school that week.
        $this->assertFalse($this->service->isSchoolDay($feb18, Region::Bratislavsky));
        $this->assertTrue($this->service->isSchoolDay($feb18, Region::Kosicky));

        $mar4 = CarbonImmutable::parse('2026-03-04');
        $this->assertTrue($this->service->isSchoolDay($mar4, Region::Bratislavsky));
        $this->assertFalse($this->service->isSchoolDay($mar4, Region::Kosicky));
    }

    public function test_school_year_is_chosen_by_date()
    {
        // A date inside the 2026/2027 year resolves to that year's bounds and breaks.
        $bounds = $this->service->schoolYearBounds(CarbonImmutable::parse('2026-09-10'));
        $this->assertSame('2027-06-30', $bounds['teaching_end']->toDateString());

        $this->assertFalse($this->service->isSchoolDay(CarbonImmutable::parse('2026-12-30'), Region::Presovsky));
        $this->assertTrue($this->service->isSchoolDay(CarbonImmutable::parse('2026-09-10'), Region::Presovsky));
    }

    public function test_nearest_holidays_are_upcoming_and_sorted()
    {
        $from = CarbonImmutable::parse('2025-10-20');
        $nearest = $this->service->nearestHolidays(Region::Zilinsky, $from, 3);

        $this->assertCount(3, $nearest);
        $this->assertSame('Jesenné prázdniny', $nearest->first()['name']);
        $this->assertSame(10, $nearest->first()['days_until']);

        $dates = $nearest->pluck('start')->map(fn ($d) => $d->timestamp)->all();
        $sorted = $dates;
        sort($sorted);
        $this->assertSame($sorted, $dates);
    }
}
