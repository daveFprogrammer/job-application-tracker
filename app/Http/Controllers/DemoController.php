<?php

namespace App\Http\Controllers;

use App\Actions\ResetDemoAccount;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DemoController extends Controller
{
    /**
     * Sign the visitor into the shared demo account.
     */
    public function store(Request $request, ResetDemoAccount $reset): RedirectResponse
    {
        abort_unless(config('demo.enabled'), 404);

        $user = User::query()->where('is_demo', true)->first() ?? $reset->handle();

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }
}
