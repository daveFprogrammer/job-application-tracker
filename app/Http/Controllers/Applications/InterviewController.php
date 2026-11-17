<?php

namespace App\Http\Controllers\Applications;

use App\Actions\SyncInterviewReminder;
use App\Http\Controllers\Controller;
use App\Http\Requests\Applications\InterviewRequest;
use App\Models\Interview;
use App\Models\JobApplication;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class InterviewController extends Controller
{
    public function store(InterviewRequest $request, JobApplication $application, SyncInterviewReminder $reminders): RedirectResponse
    {
        Gate::authorize('manage', $application);

        DB::transaction(function () use ($request, $application, $reminders) {
            $interview = $application->interviews()->create($request->safe()->except('remind'));
            $reminders->handle($interview, $request->boolean('remind'));
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Interview scheduled.')]);

        return back();
    }

    public function update(InterviewRequest $request, Interview $interview, SyncInterviewReminder $reminders): RedirectResponse
    {
        Gate::authorize('manage', $interview->jobApplication);

        DB::transaction(function () use ($request, $interview, $reminders) {
            $interview->update($request->safe()->except('remind'));
            $reminders->handle($interview, $request->boolean('remind'));
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Interview updated.')]);

        return back();
    }

    public function destroy(Interview $interview): RedirectResponse
    {
        Gate::authorize('manage', $interview->jobApplication);

        $interview->delete();

        return back();
    }
}
