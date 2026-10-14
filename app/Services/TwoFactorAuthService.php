<?php

namespace App\Services;

use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

/**
 * Thin wrapper around pragmarx/google2fa (TOTP math) and bacon/bacon-qr-code
 * (QR rendering) — deliberately not laravel/fortify or pragmarx/google2fa-
 * laravel, since this app keeps its existing Breeze auth scaffolding and
 * only needs 2FA bolted on top of it (see the Advanced Security Features
 * plan for why).
 */
class TwoFactorAuthService
{
    private Google2FA $engine;

    public function __construct()
    {
        $this->engine = new Google2FA;
    }

    public function generateSecret(): string
    {
        return $this->engine->generateSecretKey();
    }

    /**
     * An inline SVG data URI, so it never needs an external image-hosting
     * request (keeps the CSP's img-src as just 'self' + data:) and needs
     * no GD/Imagick extension — bacon-qr-code's SVG backend is pure PHP.
     */
    public function qrCodeDataUri(User $user, string $secret): string
    {
        $issuer = rawurlencode(config('app.name', 'Famville'));
        $label = rawurlencode(config('app.name', 'Famville').':'.$user->email);

        $otpauthUri = "otpauth://totp/{$label}?secret={$secret}&issuer={$issuer}&algorithm=SHA1&digits=6&period=30";

        $renderer = new ImageRenderer(new RendererStyle(240), new SvgImageBackEnd);
        $svg = (new Writer($renderer))->writeString($otpauthUri);

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }

    public function verify(string $secret, string $code): bool
    {
        // true also for the immediately-adjacent 30-second window, so a
        // slightly-out-of-sync phone clock doesn't lock the user out.
        return $this->engine->verifyKey($secret, $code) === true;
    }

    /**
     * @return array<int, string>
     */
    public function generateRecoveryCodes(int $count = 10): array
    {
        return collect(range(1, $count))
            ->map(fn () => Str::upper(Str::random(5).'-'.Str::random(5)))
            ->all();
    }
}
