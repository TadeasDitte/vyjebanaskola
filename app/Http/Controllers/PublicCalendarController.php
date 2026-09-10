<?php

namespace App\Http\Controllers;

use App\Enums\Region;
use App\Services\HolidayService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PublicCalendarController extends Controller
{
    public function __invoke(Request $request, HolidayService $holidays): Response
    {
        $region = Region::tryFrom((string) $request->query('region', ''));

        return Inertia::render('Welcome', [
            'regions' => Region::options(),
            'selectedRegion' => $region?->value,
            'stats' => $region ? $this->stats($region, $holidays) : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function stats(Region $region, HolidayService $holidays): array
    {
        $today = CarbonImmutable::now()->startOfDay();
        $end = $holidays->schoolYearBounds()['teaching_end'];
        $afterEnd = $today->greaterThan($end);

        return [
            'region_label' => $region->label(),
            'school_year_end' => $end->toDateString(),
            'days_to_school_year_end' => $afterEnd ? 0 : (int) $today->diffInDays($end),
            'raw_school_days_left' => $afterEnd ? 0 : $holidays->schoolDaysBetween($region, $today, $end),
            'nearest_holidays' => $holidays->nearestHolidays($region, $today, 6)
                ->map(fn (array $h) => [
                    'name' => $h['name'],
                    'type' => $h['type'],
                    'start' => $h['start']->toDateString(),
                    'end' => $h['end']->toDateString(),
                    'single_day' => $h['start']->isSameDay($h['end']),
                    'days_until' => $h['days_until'],
                    'ongoing' => $h['ongoing'],
                ])
                ->values()
                ->all(),
        ];
    }
}
