<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Content-Security-Policy, in Report-Only mode for now (see the security
 * pass plan: the app has inline onclick/onchange handlers and inline
 * <script> blocks that a strict policy blocks outright — Report-Only lets
 * us watch real violations without breaking anything, until those are
 * converted and this is deliberately flipped to enforced).
 *
 * Also carries the handful of secure headers that don't need a rollout
 * period — they're additive, not something that can "break" existing
 * behavior the way script-src can.
 */
class ContentSecurityPolicy
{
    public function handle(Request $request, Closure $next): Response
    {
        // Shared before the response is built, so every Blade view
        // rendered during $next() can put it on its own <script> tags via
        // nonce="{{ $cspNonce }}".
        $nonce = bin2hex(random_bytes(16));
        View::share('cspNonce', $nonce);

        /** @var Response $response */
        $response = $next($request);

        // Only the external hosts actually confirmed in use (see the
        // Advanced Security Features plan's CSP inventory) — cdn.jsdelivr.net
        // for Bootstrap/Bootstrap Icons/Chart.js, Google Fonts for type.
        // style-src keeps 'unsafe-inline' deliberately: Bootstrap and this
        // app's own inline style="" attributes depend on it, and that's a
        // materially smaller risk than script-src would be.
        $policy = implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'nonce-{$nonce}' https://cdn.jsdelivr.net",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net",
            "font-src 'self' https://fonts.gstatic.com https://cdn.jsdelivr.net",
            "img-src 'self' data:",
            "connect-src 'self'",
            "frame-src 'none'",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'none'",
        ]);

        $response->headers->set('Content-Security-Policy-Report-Only', $policy);

        // Secure headers — safe to enforce immediately, nothing depends on
        // their absence.
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'geolocation=(), camera=(), microphone=()');
        $response->headers->set('X-Frame-Options', 'DENY');

        return $response;
    }
}
