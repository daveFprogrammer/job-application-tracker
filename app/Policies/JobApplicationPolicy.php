<?php

namespace App\Policies;

use App\Models\JobApplication;
use App\Models\User;

class JobApplicationPolicy
{
    /**
     * Every action on an application (and on its interviews, notes and
     * reminders) is reserved to the user who owns it.
     */
    public function manage(User $user, JobApplication $application): bool
    {
        return $application->user_id === $user->id;
    }
}
