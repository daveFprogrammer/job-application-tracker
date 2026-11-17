<?php

namespace App\Http\Controllers\Applications;

use App\Http\Controllers\Controller;
use App\Models\JobApplication;
use App\Models\Note;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class NoteController extends Controller
{
    public function store(Request $request, JobApplication $application): RedirectResponse
    {
        Gate::authorize('manage', $application);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:10000'],
        ]);

        $application->notes()->create($validated);

        return back();
    }

    public function destroy(Note $note): RedirectResponse
    {
        Gate::authorize('manage', $note->jobApplication);

        $note->delete();

        return back();
    }
}
