<?php

namespace App\Http\Controllers;

use App\Http\Requests\TeacherRequest;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TeacherController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $this->ensureDefaultFlairs($user);

        return Inertia::render('teachers/Index', [
            'teachers' => $user->teachers()
                ->withCount('lessons')
                ->orderBy('short_code')
                ->get(),
            'flairs' => $user->flairs()->orderByDesc('sentiment')->orderBy('label')->get(),
        ]);
    }

    public function store(TeacherRequest $request): RedirectResponse
    {
        $request->user()->teachers()->create($request->validated());

        return back();
    }

    public function update(TeacherRequest $request, Teacher $teacher): RedirectResponse
    {
        $this->authorizeTeacher($request, $teacher);
        $teacher->update($request->validated());

        return back();
    }

    public function destroy(Request $request, Teacher $teacher): RedirectResponse
    {
        $this->authorizeTeacher($request, $teacher);
        $teacher->delete();

        return back();
    }

    private function authorizeTeacher(Request $request, Teacher $teacher): void
    {
        abort_unless($teacher->user_id === $request->user()->id, 403);
    }

    private function ensureDefaultFlairs(User $user): void
    {
        if ($user->flairs()->exists()) {
            return;
        }

        $defaults = [
            ['label' => 'dobrá', 'color' => '#16a34a', 'sentiment' => 1, 'position' => 0],
            ['label' => 'stredná', 'color' => '#ca8a04', 'sentiment' => 0, 'position' => 1],
            ['label' => 'zlá', 'color' => '#dc2626', 'sentiment' => -1, 'position' => 2],
        ];

        $user->flairs()->createMany($defaults);
    }
}
