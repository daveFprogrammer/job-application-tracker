<?php

namespace App\Jobs;

use App\Models\Reminder;
use App\Notifications\ReminderNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendReminder implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [60, 300];

    public function __construct(public Reminder $reminder) {}

    public function handle(): void
    {
        $reminder = $this->reminder;

        // Idempotency: a retried job must not send the same email twice.
        if ($reminder->sent_at !== null) {
            return;
        }

        $reminder->jobApplication->user->notify(new ReminderNotification($reminder));

        $reminder->forceFill(['sent_at' => now()])->save();
    }
}
