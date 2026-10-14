<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Auth::attempt() (in LoginRequest::authenticate()) both authenticates AND
 * fully logs the user in, in one call — there's no separate Auth::login()
 * step to gate 2FA inside. So this is a post-login checkpoint instead,
 * same shape as Fortify's own: the session IS authenticated the instant
 * the password check passes, but every route except the 2FA challenge
 * screen (and logout) is blocked until session('2fa_verified') is true.
 *
 * Opt-in: only users who've actually enabled 2FA (twoFactorEnabled())
 * are ever challenged — nobody is forced through this who hasn't set it
 * up themselves.
 */
class EnsureTwoFactorChallenged
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->twoFactorEnabled() || $request->session()->get('2fa_verified') === true) {
            return $next($request);
        }

        if ($request->routeIs('two-factor.challenge', 'two-factor.verify', 'two-factor.recovery', 'logout')) {
            return $next($request);
        }

        return redirect()->route('two-factor.challenge');
    }
}
