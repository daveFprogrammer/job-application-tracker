<?php

namespace App\Console\Commands;

use App\Actions\ResetDemoAccount;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('demo:reset {--if-stale : Only reset when the last reset is older than demo.reset_after_hours}')]
#[Description('Rebuild the shared demo account and its sample board')]
class ResetDemoData extends Command
{
    public function handle(ResetDemoAccount $reset): int
    {
        if (! config('demo.enabled')) {
            $this->components->info('Demo account is disabled (DEMO_ENABLED=false).');

            return self::SUCCESS;
        }

        if ($this->option('if-stale') && ! $reset->isStale()) {
            $this->components->info('Demo account is fresh, nothing to do.');

            return self::SUCCESS;
        }

        $reset->handle();

        $this->components->info('Demo account reset.');

        return self::SUCCESS;
    }
}
