<?php

namespace App\Actions;

use App\Models\Interview;
use Carbon\CarbonImmutable;

/**
 * Keeps the automatic reminder of an interview in sync with its date:
 * one day before, or one hour before when the interview is closer than that.
 */
class SyncInterviewReminder
{
    public function handle(Interview $interview, bool $enabled): void
    {
        $interview->reminder()->whereNull('sent_at')->delete();

        if (! $enabled) {
            return;
        }

        $remindAt = $this->remindAt($interview->scheduled_at);

        if ($remindAt === null) {
            return;
        }

        $application = $interview->jobApplication;

        $application->reminders()->create([
            'interview_id' => $interview->id,
            'remind_at' => $remindAt,
            'message' => sprintf(
                '%s interview for %s at %s on %s',
                $interview->type->label(),
                $application->position,
                $application->company,
                $interview->scheduled_at->format('D j M, H:i'),
            ),
        ]);
    }

    private function remindAt(CarbonImmutable $scheduledAt): ?CarbonImmutable
    {
        foreach ([$scheduledAt->subDay(), $scheduledAt->subHour()] as $candidate) {
            if ($candidate->isFuture()) {
                return $candidate;
            }
        }

        return null;
    }
}
