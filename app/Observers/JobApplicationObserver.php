<?php

namespace App\Observers;

use App\Enums\ApplicationStatus;
use App\Models\JobApplication;

/**
 * Records every status change, whatever triggered it: the create form,
 * the edit form or drag and drop on the board (MoveJobApplication).
 */
class JobApplicationObserver
{
    public function created(JobApplication $application): void
    {
        $application->statusChanges()->create([
            'from_status' => null,
            'to_status' => $application->status,
            'changed_at' => now(),
        ]);
    }

    public function updated(JobApplication $application): void
    {
        if (! $application->wasChanged('status')) {
            return;
        }

        $from = $application->getOriginal('status');

        $application->statusChanges()->create([
            'from_status' => $from instanceof ApplicationStatus ? $from : ApplicationStatus::tryFrom((string) $from),
            'to_status' => $application->status,
            'changed_at' => now(),
        ]);
    }
}
