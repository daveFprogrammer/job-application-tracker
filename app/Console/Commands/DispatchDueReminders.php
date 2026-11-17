<?php

namespace App\Console\Commands;

use App\Jobs\SendReminder;
use App\Models\Reminder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('reminders:dispatch')]
#[Description('Queue an email for every reminder that is due')]
class DispatchDueReminders extends Command
{
    public function handle(): int
    {
        $dispatched = 0;

        Reminder::query()->due()->orderBy('remind_at')->each(function (Reminder $reminder) use (&$dispatched) {
            // Claim the reminder atomically, so overlapping runs never queue it twice.
            $claimed = Reminder::query()
                ->whereKey($reminder->id)
                ->whereNull('queued_at')
                ->update(['queued_at' => now()]);

            if ($claimed === 1) {
                SendReminder::dispatch($reminder);
                $dispatched++;
            }
        });

        $this->components->info("Dispatched {$dispatched} reminder(s).");

        return self::SUCCESS;
    }
}
