<?php

use Illuminate\Support\Facades\Schedule;

/*
| When a real scheduler is available (`php artisan schedule:work` locally with
| Sail, or a cron entry on a VPS) due reminders are queued every minute.
| On Render's free tier the same command is triggered over HTTP instead,
| see App\Http\Controllers\CronController.
*/
Schedule::command('reminders:dispatch')->everyMinute()->withoutOverlapping();

Schedule::command('demo:reset --if-stale')->hourly()->withoutOverlapping();
