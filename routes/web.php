<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FlairController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\PublicCalendarController;
use App\Http\Controllers\TeacherController;
use Illuminate\Support\Facades\Route;

Route::get('/', PublicCalendarController::class)->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::get('schedule', [LessonController::class, 'index'])->name('schedule.index');
    Route::post('schedule', [LessonController::class, 'store'])->name('schedule.store');
    Route::put('schedule/{lesson}', [LessonController::class, 'update'])->name('schedule.update');
    Route::delete('schedule/{lesson}', [LessonController::class, 'destroy'])->name('schedule.destroy');

    Route::get('teachers', [TeacherController::class, 'index'])->name('teachers.index');
    Route::post('teachers', [TeacherController::class, 'store'])->name('teachers.store');
    Route::put('teachers/{teacher}', [TeacherController::class, 'update'])->name('teachers.update');
    Route::delete('teachers/{teacher}', [TeacherController::class, 'destroy'])->name('teachers.destroy');

    Route::post('flairs', [FlairController::class, 'store'])->name('flairs.store');
    Route::put('flairs/{flair}', [FlairController::class, 'update'])->name('flairs.update');
    Route::delete('flairs/{flair}', [FlairController::class, 'destroy'])->name('flairs.destroy');
});

require __DIR__.'/settings.php';
