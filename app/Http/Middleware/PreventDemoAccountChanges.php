<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * The demo account is shared by every visitor: its board can be edited
 * freely (it is rebuilt every day), but its credentials and profile cannot,
 * otherwise anyone could take it over or lock the others out.
 */
class PreventDemoAccountChanges
{
    /** @var list<string> */
    public const PROTECTED_ROUTES = [
        'profile.update',
        'profile.destroy',
        'user-password.update',
        'password.confirm.store',
        'verification.send',
        'two-factor.*',
        'passkey.store',
        'passkey.destroy',
        'passkey.confirm',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->is_demo && $request->routeIs(self::PROTECTED_ROUTES)) {
            if (! $request->header('X-Inertia')) {
                abort(403, 'The demo account cannot be changed.');
            }

            Inertia::flash('toast', [
                'type' => 'warning',
                'message' => __('The demo account is read-only here: create your own account to change it.'),
            ]);

            return back();
        }

        return $next($request);
    }
}
