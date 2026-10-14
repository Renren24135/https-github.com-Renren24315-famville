<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class PayMongoService
{
    private const BASE_URL = 'https://api.paymongo.com/v1';

    /**
     * Create a GCash e-wallet source for the given amount — the customer
     * is sent to the returned checkout_url to actually pay. Confirmation
     * comes later, from the webhook, never from this call or the
     * redirect-back alone.
     *
     * @return array{id: string, checkout_url: string}
     */
    public function createGcashSource(float $amount, string $description, string $redirectSuccessUrl, string $redirectFailedUrl): array
    {
        $response = $this->client()->post(self::BASE_URL.'/sources', [
            'data' => [
                'attributes' => [
                    // PayMongo amounts are in centavos, not pesos.
                    'amount' => (int) round($amount * 100),
                    'redirect' => [
                        'success' => $redirectSuccessUrl,
                        'failed' => $redirectFailedUrl,
                    ],
                    'type' => 'gcash',
                    'currency' => 'PHP',
                    'description' => $description,
                ],
            ],
        ]);

        if ($response->failed()) {
            Log::error('PayMongo source creation failed.', ['body' => $response->body()]);

            throw new RuntimeException('Could not start the GCash payment right now. Please try again.');
        }

        $data = $response->json('data');

        return [
            'id' => $data['id'],
            'checkout_url' => $data['attributes']['redirect']['checkout_url'],
        ];
    }

    /**
     * Retrieve a source's current status — used by the webhook to confirm
     * what it's being told about before acting on it, rather than trusting
     * the webhook payload's own status field blindly.
     */
    public function retrieveSource(string $sourceId): array
    {
        $response = $this->client()->get(self::BASE_URL.'/sources/'.$sourceId);

        if ($response->failed()) {
            Log::error('PayMongo source retrieval failed.', ['source_id' => $sourceId, 'body' => $response->body()]);

            throw new RuntimeException('Could not verify the PayMongo source.');
        }

        return $response->json('data');
    }

    /**
     * Charge a source once PayMongo reports it as "chargeable" (the
     * customer has authorized the GCash payment on PayMongo's page).
     */
    public function createPaymentFromSource(string $sourceId, float $amount, string $description): array
    {
        $response = $this->client()->post(self::BASE_URL.'/payments', [
            'data' => [
                'attributes' => [
                    'amount' => (int) round($amount * 100),
                    'currency' => 'PHP',
                    'description' => $description,
                    'source' => [
                        'id' => $sourceId,
                        'type' => 'source',
                    ],
                ],
            ],
        ]);

        if ($response->failed()) {
            Log::error('PayMongo payment creation failed.', ['source_id' => $sourceId, 'body' => $response->body()]);

            throw new RuntimeException('Could not charge the PayMongo source.');
        }

        return $response->json('data');
    }

    /**
     * Verifies a webhook payload actually came from PayMongo before any
     * of its contents are trusted — PayMongo signs the raw request body
     * with HMAC-SHA256 using the webhook's own signing secret.
     */
    public function verifyWebhookSignature(string $payload, string $signatureHeader): bool
    {
        $secret = config('services.paymongo.webhook_secret');

        if (! $secret) {
            Log::warning('PayMongo webhook secret is not configured — rejecting webhook.');

            return false;
        }

        // Header looks like: t=169...,te=abcd...,li=abcd... — "te" is the
        // test-mode signature, "li" is live-mode. Try whichever is present.
        $parts = [];
        foreach (explode(',', $signatureHeader) as $pair) {
            [$key, $value] = array_pad(explode('=', $pair, 2), 2, null);
            $parts[$key] = $value;
        }

        $timestamp = $parts['t'] ?? null;
        // In test mode PayMongo sends "li=" (present but empty) alongside
        // a real "te" value, and vice versa in live mode — `??` only
        // falls through on null, not on an empty string, so it was
        // picking up the blank one. `?:` treats '' as falsy too.
        $signature = ($parts['li'] ?? null) ?: ($parts['te'] ?? null);

        if (! $timestamp || ! $signature) {
            return false;
        }

        $expected = hash_hmac('sha256', $timestamp.'.'.$payload, $secret);

        return hash_equals($expected, $signature);
    }

    private function client()
    {
        return Http::withBasicAuth(config('services.paymongo.secret'), '')
            ->acceptJson()
            ->asJson();
    }
}
