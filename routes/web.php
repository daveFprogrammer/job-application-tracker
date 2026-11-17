<?php

use App\Http\Controllers\Applications\InterviewController;
use App\Http\Controllers\Applications\NoteController;
use App\Http\Controllers\Applications\ReminderController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\JobApplicationController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::patch('applications/{application}/move', [JobApplicationController::class, 'move'])
        ->name('applications.move');
    Route::resource('applications', JobApplicationController::class);

    Route::post('applications/{application}/interviews', [InterviewController::class, 'store'])
        ->name('interviews.store');
    Route::put('interviews/{interview}', [InterviewController::class, 'update'])->name('interviews.update');
    Route::delete('interviews/{interview}', [InterviewController::class, 'destroy'])->name('interviews.destroy');

    Route::post('applications/{application}/notes', [NoteController::class, 'store'])->name('notes.store');
    Route::delete('notes/{note}', [NoteController::class, 'destroy'])->name('notes.destroy');

    Route::post('applications/{application}/reminders', [ReminderController::class, 'store'])
        ->name('reminders.store');
    Route::delete('reminders/{reminder}', [ReminderController::class, 'destroy'])->name('reminders.destroy');
});

require __DIR__.'/settings.php';
