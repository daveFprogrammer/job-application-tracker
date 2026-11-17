<?php

namespace App\Http\Controllers\Applications;

use App\Http\Controllers\Controller;
use App\Http\Requests\Applications\ReminderRequest;
use App\Models\JobApplication;
use App\Models\Reminder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class ReminderController extends Controller
{
    public function store(ReminderRequest $request, JobApplication $application): RedirectResponse
    {
        Gate::authorize('manage', $application);

        $application->reminders()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Reminder set.')]);

        return back();
    }

    public function destroy(Reminder $reminder): RedirectResponse
    {
        Gate::authorize('manage', $reminder->jobApplication);

        $reminder->delete();

        return back();
    }
}
