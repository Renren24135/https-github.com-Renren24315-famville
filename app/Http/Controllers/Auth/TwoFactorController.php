<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\TwoFactorAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TwoFactorController extends Controller
{
    public function __construct(private readonly TwoFactorAuthService $twoFactor) {}

    public function showChallenge(): View|RedirectResponse
    {
        $user = Auth::user();

        if (! $user || ! $user->twoFactorEnabled() || session('2fa_verified') === true) {
            return redirect()->route('dashboard');
        }

        return view('auth.two-factor-challenge');
    }

    public function verifyChallenge(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $this->ensureIsNotRateLimited($user->id);

        $request->validate(['code' => ['required', 'string']]);

        if (! $this->twoFactor->verify($user->two_factor_secret, $request->string('code')->toString())) {
            RateLimiter::hit($this->throttleKey($user->id), 60);

            return back()->withErrors(['code' => 'That code is incorrect or expired.']);
        }

        RateLimiter::clear($this->throttleKey($user->id));
        $request->session()->put('2fa_verified', true);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function useRecoveryCode(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $this->ensureIsNotRateLimited($user->id);

        $request->validate(['recovery_code' => ['required', 'string']]);
        $submitted = Str::upper(trim($request->string('recovery_code')->toString()));

        $codes = $user->two_factor_recovery_codes ? json_decode($user->two_factor_recovery_codes, true) : [];
        $index = array_search($submitted, $codes, true);

        if ($index === false) {
            RateLimiter::hit($this->throttleKey($user->id), 60);

            return back()->withErrors(['recovery_code' => 'That recovery code is invalid or was already used.']);
        }

        // Single-use — remove it so it can't be replayed.
        unset($codes[$index]);
        $user->update(['two_factor_recovery_codes' => json_encode(array_values($codes))]);

        RateLimiter::clear($this->throttleKey($user->id));
        $request->session()->put('2fa_verified', true);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'))
            ->with('status', 'Signed in with a recovery code. '.count($codes).' recovery code(s) remaining — generate new ones from your security settings if you\'re running low.');
    }

    /**
     * Same shape as LoginRequest's own rate limiting, just keyed by the
     * already-authenticated user's id instead of email+IP — the password
     * gate is already past, but the OTP gate shouldn't be any weaker.
     */
    private function ensureIsNotRateLimited(int $userId): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey($userId), 5)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey($userId));

        abort(429, "Too many attempts. Please try again in {$seconds} seconds.");
    }

    private function throttleKey(int $userId): string
    {
        return 'two-factor-verify:'.$userId;
    }
}
