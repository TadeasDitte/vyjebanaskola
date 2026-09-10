<?php

namespace Tests\Feature;

use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PublicCalendarControllerTest extends TestCase
{
    public function test_front_page_renders_for_guests_without_a_region()
    {
        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Welcome')
                ->has('regions', 8)
                ->where('selectedRegion', null)
                ->where('stats', null),
            );
    }

    public function test_selecting_a_region_returns_the_stats_payload()
    {
        $this->get('/?region=kosicky')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('selectedRegion', 'kosicky')
                ->has('stats', fn (Assert $stats) => $stats
                    ->where('region_label', 'Košický kraj')
                    ->has('school_year_end')
                    ->has('days_to_school_year_end')
                    ->has('raw_school_days_left')
                    ->has('nearest_holidays'),
                ),
            );
    }

    public function test_invalid_region_is_ignored()
    {
        $this->get('/?region=narnia')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('selectedRegion', null)
                ->where('stats', null),
            );
    }
}
