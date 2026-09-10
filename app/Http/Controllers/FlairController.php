<?php

namespace App\Http\Controllers;

use App\Http\Requests\FlairRequest;
use App\Models\Flair;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FlairController extends Controller
{
    public function store(FlairRequest $request): RedirectResponse
    {
        $request->user()->flairs()->create($request->validated() + [
            'position' => (int) $request->user()->flairs()->max('position') + 1,
        ]);

        return back();
    }

    public function update(FlairRequest $request, Flair $flair): RedirectResponse
    {
        $this->authorizeFlair($request, $flair);
        $flair->update($request->validated());

        return back();
    }

    public function destroy(Request $request, Flair $flair): RedirectResponse
    {
        $this->authorizeFlair($request, $flair);
        $flair->delete();

        return back();
    }

    private function authorizeFlair(Request $request, Flair $flair): void
    {
        abort_unless($flair->user_id === $request->user()->id, 403);
    }
}
