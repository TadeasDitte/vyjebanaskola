<?php

namespace App\Http\Controllers;

use App\Enums\Region;
use App\Services\ClassCountService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, ClassCountService $classCounts): Response
    {
        $user = $request->user();

        return Inertia::render('Dashboard', [
            'projection' => $classCounts->projection($user),
            'regions' => Region::options(),
            'region' => $user->region?->value,
            'regionLabel' => $user->region?->label(),
            'weekParityAnchor' => $user->week_parity_anchor?->toDateString(),
            'hasLessons' => $user->lessons()->exists(),
        ]);
    }
}
