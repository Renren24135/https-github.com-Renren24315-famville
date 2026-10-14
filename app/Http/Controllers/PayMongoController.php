<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\User;
use App\Services\BookingCalculator;
use App\Services\PayMongoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class PayMongoController extends Controller
{
    public function __construct(private readonly PayMongoService $payMongo) {}

    public function payFee(Booking $booking): RedirectResponse
    {
        abort_unless($booking->user_id === auth()->id(), 403);
        abort_if($booking->reservation_fee <= 0, 422, 'This booking has no reservation fee to pay.');
        abort_if($booking->reservation_fee_status->value === 'paid', 422, 'The reservation fee is already paid.');

        try {
            $source = $this->payMongo->createGcashSource(
                (float) $booking->reservation_fee,
                'Famville reservation fee — '.$booking->bookingNo(),
                route('paymongo.return', ['booking' => $booking->id]),
                route('paymongo.return', ['booking' => $booking->id, 'failed' => 1]),
            );
        } catch (RuntimeException $e) {
            return back()->withErrors(['asset_type' => $e->getMessage()]);
        } catch (Throwable $e) {
            // Network/connection-level failures (DNS, SSL, timeout) don't
            // extend RuntimeException the way our own thrown errors do —
            // caught separately so a connectivity hiccup degrades to a
            // normal error message instead of a 500.
            Log::error('PayMongo pay-fee failed unexpectedly.', ['error' => $e->getMessage()]);

            return back()->withErrors(['asset_type' => 'Could not start the GCash payment right now. Please try again.']);
        }

        $booking->update(['paymongo_fee_source_id' => $source['id']]);

        return redirect()->away($source['checkout_url']);
    }

    public function payBalance(Booking $booking): RedirectResponse
    {
        abort_unless($booking->user_id === auth()->id(), 403);
        abort_if($booking->payment_status->value === 'paid', 422, 'This booking is already paid.');
        abort_if($booking->balance_payment_method?->value !== 'gcash', 422, 'This booking\'s balance isn\'t marked for GCash payment.');

        $isOpenTimeTable = $booking->bookable instanceof \App\Models\BilliardTable && $booking->hours === null;
        abort_if($isOpenTimeTable, 422, 'Open Time bookings are billed at checkout, once the session ends.');

        try {
            $source = $this->payMongo->createGcashSource(
                (float) $booking->remainingBalance(),
                'Famville session balance — '.$booking->bookingNo(),
                route('paymongo.return', ['booking' => $booking->id]),
                route('paymongo.return', ['booking' => $booking->id, 'failed' => 1]),
            );
        } catch (RuntimeException $e) {
            return back()->withErrors(['asset_type' => $e->getMessage()]);
        } catch (Throwable $e) {
            Log::error('PayMongo pay-balance failed unexpectedly.', ['error' => $e->getMessage()]);

            return back()->withErrors(['asset_type' => 'Could not start the GCash payment right now. Please try again.']);
        }

        $booking->update(['paymongo_balance_source_id' => $source['id']]);

        return redirect()->away($source['checkout_url']);
    }

    /**
     * Where PayMongo redirects the customer back after they pay or cancel.
     * This is informational only — it never marks anything as paid itself,
     * since the customer could close the tab before reaching this page.
     * The webhook below is the only trustworthy confirmation.
     */
    public function return(Request $request, Booking $booking): View
    {
        abort_unless($booking->user_id === auth()->id(), 403);

        $failed = $request->boolean('failed');

        return view('customer.bookings.paymongo-return', compact('booking', 'failed'));
    }

    public function webhook(Request $request): Response
    {
        $payload = $request->getContent();
        $signature = $request->header('Paymongo-Signature', '');

        if (! $this->payMongo->verifyWebhookSignature($payload, $signature)) {
            Log::warning('Rejected PayMongo webhook with invalid signature.');

            return response('Invalid signature', 400);
        }

        $event = $request->input('data.attributes.type');
        $resource = $request->input('data.attributes.data');

        try {
            match ($event) {
                'source.chargeable' => $this->handleSourceChargeable($resource),
                'payment.paid' => $this->handlePaymentPaid($resource),
                'payment.failed' => $this->handlePaymentFailed($resource),
                default => null,
            };
        } catch (Throwable $e) {
            // Logged, not thrown — a 5xx here makes PayMongo retry
            // indefinitely, and a bug in our handling of one event
            // shouldn't look like "try again" to PayMongo's retry logic.
            Log::error('PayMongo webhook handling failed.', ['event' => $event, 'error' => $e->getMessage()]);
        }

        return response('OK', 200);
    }

    private function handleSourceChargeable(array $resource): void
    {
        $sourceId = $resource['id'];
        $amount = $resource['attributes']['amount'] / 100;

        $this->payMongo->createPaymentFromSource($sourceId, $amount, 'Famville GCash payment');
    }

    private function handlePaymentPaid(array $resource): void
    {
        $sourceId = $resource['attributes']['source']['id'] ?? null;
        $paymongoPaymentId = $resource['id'];

        if (! $sourceId) {
            return;
        }

        $systemCashierId = User::where('email', 'paymongo@system.famville.local')->value('id');

        if (! $systemCashierId) {
            Log::error('PayMongo system user is missing — run the PayMongoSystemUserSeeder.');

            return;
        }

        if ($booking = Booking::where('paymongo_fee_source_id', $sourceId)->first()) {
            // Idempotent — a redelivered webhook must not double-charge.
            if ($booking->reservation_fee_status->value !== 'paid') {
                BookingCalculator::confirmReservationFeePaid($booking, $systemCashierId, 'gcash', null, $paymongoPaymentId);
            }

            return;
        }

        if ($booking = Booking::where('paymongo_balance_source_id', $sourceId)->first()) {
            if ($booking->payment_status->value !== 'paid') {
                BookingCalculator::confirmBalancePaid($booking, $systemCashierId, 'gcash', null, $paymongoPaymentId);
            }
        }
    }

    private function handlePaymentFailed(array $resource): void
    {
        $sourceId = $resource['attributes']['source']['id'] ?? null;

        if (! $sourceId) {
            return;
        }

        if ($booking = Booking::where('paymongo_fee_source_id', $sourceId)->first()) {
            if ($booking->reservation_fee_status->value !== 'paid') {
                $booking->update(['reservation_fee_status' => 'unpaid', 'paymongo_fee_source_id' => null]);
            }

            return;
        }

        if ($booking = Booking::where('paymongo_balance_source_id', $sourceId)->first()) {
            if ($booking->payment_status->value !== 'paid') {
                $booking->update(['paymongo_balance_source_id' => null]);
            }
        }
    }
}
