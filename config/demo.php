<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Demo account
    |--------------------------------------------------------------------------
    |
    | When enabled, the landing and login pages offer a one-click "Try the
    | demo" that signs visitors into a shared account with a sample board.
    | Its data is rebuilt every `reset_after_hours`, its emails are never
    | sent and its profile and security settings are read-only.
    |
    */

    'enabled' => (bool) env('DEMO_ENABLED', false),

    'email' => env('DEMO_EMAIL', 'demo@job-application-tracker.test'),

    'reset_after_hours' => (int) env('DEMO_RESET_AFTER_HOURS', 24),

];
