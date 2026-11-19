<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

/**
 * HTTP-triggered "cron" for free hosting tiers (e.g. Render free) where
 * neither a cron job nor an always-on queue worker is available.
 *
 * An external scheduler (the GitHub Actions workflow in .github/workflows)
 * calls this endpoint periodically: it wakes the instance up, queues the
 * reminders that are due and drains the queue, then lets everything sleep.
 */
class CronController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $secret = config('services.cron.secret');

        abort_unless(is_string($secret) && $secret !== '', 404);
        abort_unless(hash_equals($secret, (string) $request->bearerToken()), 401);

        Artisan::call('reminders:dispatch');
        $dispatched = trim(Artisan::output());

        Artisan::call('queue:work', [
            '--stop-when-empty' => true,
            '--max-time' => 50,
            '--tries' => 3,
        ]);

        // The demo account is rebuilt once a day (no-op when disabled or fresh).
        Artisan::call('demo:reset', ['--if-stale' => true]);
        $demo = trim(Artisan::output());

        return response()->json([
            'reminders' => $dispatched,
            'demo' => $demo,
            'ran_at' => now()->toIso8601String(),
        ]);
    }
}
