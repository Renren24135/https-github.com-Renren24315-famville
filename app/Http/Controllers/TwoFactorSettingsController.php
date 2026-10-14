<?php

namespace App\Http\Controllers;

use App\Services\TwoFactorAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TwoFactorSettingsController extends Controller
{
    public function __construct(private readonly TwoFactorAuthService $twoFactor) {}

    public function show(Request $request): View
    {
        $user = $request->user();

        // A fresh, unconfirmed secret is generated and held in the
        // session (not saved to the user) until enable() verifies a real
        // code against it — avoids ever persisting a secret the user
        // never actually finished setting up.
        if (! $user->twoFactorEnabled() && ! $request->session()->has('pending_2fa_secret')) {
            $request->session()->put('pending_2fa_secret', $this->twoFactor->generateSecret());
        }

        $secret = $user->twoFactorEnabled() ? null : $request->session()->get('pending_2fa_secret');
        $qrCode = $secret ? $this->twoFactor->qrCodeDataUri($user, $secret) : null;

        return view('profile.two-factor', [
            'enabled' => $user->twoFactorEnabled(),
            'secret' => $secret,
            'qrCode' => $qrCode,
        ]);
    }

    public function enable(Request $request): RedirectResponse
    {
        $user = $request->user();
        $secret = $request->session()->get('pending_2fa_secret');

        abort_unless($secret, 422, 'Start the setup again — your pending setup expired.');

        $request->validate(['code' => ['required', 'string']]);

        if (! $this->twoFactor->verify($secret, $request->string('code')->toString())) {
            return back()->withErrors(['code' => 'That code is incorrect. Check your authenticator app and try again.']);
        }

        $recoveryCodes = $this->twoFactor->generateRecoveryCodes();

        $user->update([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => json_encode($recoveryCodes),
            'two_factor_confirmed_at' => now(),
        ]);

        $request->session()->forget('pending_2fa_secret');
        // Already fully signed in for this session — no need to make them
        // immediately re-challenge the 2FA they just set up.
        $request->session()->put('2fa_verified', true);

        return redirect()->route('two-factor.show')
            ->with('status', 'Two-factor authentication is now on.')
            ->with('recoveryCodes', $recoveryCodes);
    }

    public function disable(Request $request): RedirectResponse
    {
        $request->user()->update([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ]);

        return redirect()->route('two-factor.show')->with('status', 'Two-factor authentication is now off.');
    }

    public function regenerateRecoveryCodes(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->twoFactorEnabled(), 422, 'Enable two-factor authentication first.');

        $recoveryCodes = $this->twoFactor->generateRecoveryCodes();
        $user->update(['two_factor_recovery_codes' => json_encode($recoveryCodes)]);

        return redirect()->route('two-factor.show')
            ->with('status', 'New recovery codes generated — your old ones no longer work.')
            ->with('recoveryCodes', $recoveryCodes);
    }
}
