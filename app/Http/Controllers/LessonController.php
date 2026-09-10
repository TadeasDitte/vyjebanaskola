<?php

namespace App\Http\Controllers;

use App\Enums\WeekParity;
use App\Http\Requests\LessonRequest;
use App\Models\Lesson;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LessonController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('schedule/Index', [
            'lessons' => $user->lessons()
                ->orderBy('day_of_week')
                ->orderBy('period')
                ->get(),
            'teachers' => $user->teachers()->orderBy('short_code')->get(['id', 'short_code', 'full_name']),
            'weekParities' => WeekParity::options(),
            'hasParityAnchor' => $user->week_parity_anchor !== null,
        ]);
    }

    public function store(LessonRequest $request): RedirectResponse
    {
        $request->user()->lessons()->create($request->validated());

        return back();
    }

    public function update(LessonRequest $request, Lesson $lesson): RedirectResponse
    {
        $this->authorizeLesson($request, $lesson);
        $lesson->update($request->validated());

        return back();
    }

    public function destroy(Request $request, Lesson $lesson): RedirectResponse
    {
        $this->authorizeLesson($request, $lesson);
        $lesson->delete();

        return back();
    }

    private function authorizeLesson(Request $request, Lesson $lesson): void
    {
        abort_unless($lesson->user_id === $request->user()->id, 403);
    }
}
