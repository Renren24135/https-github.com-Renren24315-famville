# Deploying Famville Behind Cloudflare

Laravel is an application framework, not a WAF — it enforces things the moment a request reaches PHP (CSRF, auth, validation, rate limits on specific routes), but it has no way to stop a flood of requests before they consume a server process, fingerprint and block bot traffic, or absorb a volumetric DDoS. That's infrastructure-layer work. Cloudflare, sitting in front of the app as a reverse proxy, is where that belongs. This doc covers what to configure on Cloudflare's side, and what Laravel already does on its side so the two work together instead of fighting each other.

## 1. DNS and proxy mode

- Point the production domain's `A`/`CNAME` record at the app server through Cloudflare, with the proxy status set to **Proxied** (orange cloud) — not **DNS only** (grey cloud). Grey-cloud bypasses Cloudflare entirely for that record, which defeats the point of all of the below.

## 2. SSL/TLS

- **SSL/TLS → Overview**: set the mode to **Full (strict)**. This requires a valid TLS certificate on the origin server itself (not just at Cloudflare's edge) — use a free cert from Let's Encrypt, or your host's own. Full (strict) is the only mode that actually encrypts the Cloudflare-to-origin hop with a verified certificate; "Flexible" leaves that hop in plaintext, which defeats `ForceHttps` middleware's entire purpose (the middleware sees `$request->secure()` as true because Cloudflare told it so over an unencrypted internal connection).
- **SSL/TLS → Edge Certificates**: turn on **Always Use HTTPS** (redirects any stray `http://` request at the edge, before it even reaches the app) and **HSTS** (`Strict-Transport-Security`), once you've confirmed HTTPS works end-to-end — HSTS is hard to safely undo once browsers cache it, so verify Full (strict) is solid first.

## 3. Trusted proxy configuration (already built, just needs the env var)

`bootstrap/app.php` already has:

```php
if ($trustedProxies = env('APP_TRUSTED_PROXIES')) {
    $middleware->trustProxies(at: $trustedProxies);
}
```

Without this, every request appears to Laravel as coming from Cloudflare's own IP (since Cloudflare is the one actually connecting to the origin), breaking `$request->ip()` (used by the login rate limiter, the 2FA verify rate limiter, and anywhere `request()->ip()` is logged in the activity log) and potentially `$request->secure()`.

Set in production's `.env`:

```
APP_TRUSTED_PROXIES="173.245.48.0/20,103.21.244.0/22,103.22.200.0/22,103.31.4.0/22,141.101.64.0/18,108.162.192.0/18,190.93.240.0/20,188.114.96.0/20,197.234.240.0/22,198.41.128.0/17,162.158.0.0/15,104.16.0.0/13,104.24.0.0/14,172.64.0.0/13,131.0.72.0/22,2400:cb00::/32,2606:4700::/32,2803:f800::/32,2405:b500::/32,2405:8100::/32,2a06:98c0::/29,2c0f:f248::/32"
```

This is Cloudflare's published IP range list. **It changes occasionally** — check `https://www.cloudflare.com/ips/` before each production deploy and update the env var if it's drifted, rather than copying this list once and forgetting it.

## 4. WAF rules

**Security → WAF → Managed rules**: turn on the **Cloudflare Managed Ruleset** and the **OWASP Core Ruleset** (both included on every plan, including free). These catch the generic stuff — SQL injection payloads, known exploit signatures, malformed requests — before they reach PHP at all.

**Security → WAF → Custom rules**, two worth adding specifically for this app:

- Block requests to `/.env`, `/.git/*`, `/storage/*`, `/vendor/*` — none of these should ever be requested by a legitimate browser, and they're common scanner targets for a Laravel app specifically.
- Challenge (don't outright block — real cashiers/admins sometimes work from flaky connections) anything hitting `/login`, `/two-factor/challenge`, or `/two-factor/recovery` with an unusually high request rate from one IP — belt-and-suspenders with Laravel's own rate limiting below.

## 5. Rate limiting rules (Cloudflare edge, on top of Laravel's own)

Laravel already rate-limits these at the application level:

| Route | Limit | Where |
|---|---|---|
| `/login` | 5 attempts, then 60s → 3600s progressive lockout | `LoginRequest::ensureIsNotRateLimited()` |
| `/two-factor/challenge`, `/two-factor/recovery` | 5 attempts/60s + `throttle:10,1` | `TwoFactorController` + route middleware |
| `/customer/bookings` (POST) | `throttle:10,1` | `routes/web.php` |
| `/staff/pos/walk-in` | `throttle:10,1` | `routes/web.php` |
| `/staff/pos/{booking}/pay` | `throttle:20,1` | `routes/web.php` |

These stop a single attacker from brute-forcing or spamming once a request reaches the app — but each blocked request still costs a PHP process. **Security → WAF → Rate limiting rules** on Cloudflare stops the same abuse at the edge, before it costs you anything server-side. Mirror the table above loosely (e.g. 20 requests/minute to `/login` per IP → Block for 1 hour) — set the Cloudflare limit a bit looser than Laravel's own, so Laravel's friendlier progressive-lockout message is what a real user sees first, and Cloudflare's harder block only kicks in for traffic that's clearly automated.

## 6. Bot protection

**Security → Bots**: turn on **Bot Fight Mode** (free tier) or **Super Bot Fight Mode** (paid tiers) — this challenges/blocks known bot signatures (scrapers, headless browsers, credential-stuffing tools) before they reach the login or booking forms. Doesn't require any app-side change.

## 7. DDoS protection

On by default for every proxied domain — no configuration needed. Worth confirming in **Security → DDoS** that it hasn't been accidentally set to a custom/relaxed sensitivity.

## 8. What NOT to rely on Cloudflare for

- **CSRF protection** — still Laravel's `VerifyCsrfToken` middleware; Cloudflare has no concept of your app's CSRF tokens.
- **Authentication/authorization** — still entirely server-side (Breeze + Spatie Permission + 2FA). Cloudflare Access can gate an *entire* subdomain behind SSO if you ever want a staff-only subdomain, but that's a separate decision, not a substitute for the app's own role checks.
- **Input validation** — the WAF managed rules catch generic attack signatures, not this app's specific business rules (e.g. a booking's `scoresheets` must be 1–20). That's still Form Requests.
