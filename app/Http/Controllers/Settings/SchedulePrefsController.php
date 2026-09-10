<?php

namespace App\Http\Controllers\Settings;

use App\Enums\Region;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;

class SchedulePrefsController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'region' => ['nullable', new Enum(Region::class)],
            'week_parity_anchor' => ['nullable', 'date'],
        ]);

        $request->user()->update([
            'region' => $validated['region'] ?? null,
            'week_parity_anchor' => $validated['week_parity_anchor'] ?? null,
        ]);

        return back();
    }
}
