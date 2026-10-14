@extends('layouts.app')

@section('title', 'Booking Details')

@section('content')
    @php($isOpenTable = $booking->bookable instanceof \App\Models\BilliardTable && $booking->hours === null)
    @php($isLane = $booking->bookable_type === \App\Models\Lane::class)

    <a href="{{ route('customer.bookings.index') }}" class="text-muted small text-decoration-none d-inline-block mb-3"><i class="bi bi-arrow-left"></i> Back to My Bookings</a>

    <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4">
        <div>
            <div class="fv-eyebrow mb-1"><i class="bi {{ $isLane ? 'bi-align-center' : 'bi-circle' }}"></i> {{ $isLane ? 'Bowling' : 'Billiards' }}</div>
            <h1 class="h3 mb-1">{{ $booking->bookable->name ?? $booking->assetLabel() }}</h1>
            <div class="fv-ref">{{ $booking->bookingNo() }} &mdash; quote this to the cashier when you arrive.</div>
        </div>
        <x-fv-status-dot :color="$booking->status->color()" :label="$booking->status->label()" />
    </div>

    @if ($booking->status === \App\Enums\BookingStatus::Approved)
        <div class="alert alert-warning" style="max-width: 640px;">
            <i class="bi bi-clock-history"></i> <strong>Please arrive on time.</strong> If you arrive more than <strong>10 minutes</strong> after your scheduled start time, this reservation will be cancelled and the lane/table released.
        </div>
    @endif

    <div class="row g-4" style="max-width: 900px;">
        <div class="col-md-6">
            <div class="fv-stat-tile h-100">
                <div class="fv-stat-tile-label"><i class="bi bi-calendar-event"></i> Date &amp; Time</div>
                <div class="fv-stat-tile-value fs-5">{{ $booking->booking_date->format('M d, Y') }}</div>
                <div class="fv-stat-tile-sub fv-mono">{{ \Illuminate\Support\Carbon::parse($booking->start_time)->format('g:i A') }}</div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="fv-stat-tile h-100">
                <div class="fv-stat-tile-label"><i class="bi bi-stopwatch"></i> Quantity</div>
                <div class="fv-stat-tile-value fs-5">
                    @if ($booking->scoresheets !== null)
                        {{ $booking->scoresheets }} scoresheet(s)
                    @elseif ($booking->hours !== null)
                        {{ $booking->hours }} hour(s)
                    @else
                        Open Time
                    @endif
                </div>
                <div class="fv-stat-tile-sub">{{ $booking->status->value === 'completed' ? 'played' : ($isOpenTable ? 'billed hourly when finished' : 'booked') }}</div>
            </div>
        </div>
    </div>

    <x-fv-lane-divider />

    <div class="card shadow-sm" style="max-width: 640px;">
        <div class="card-header"><i class="bi bi-receipt"></i> Payment Breakdown</div>
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-4">Total Amount</dt>
                <dd class="col-sm-8 fv-mono">
                    @if ($isOpenTable && $booking->status->value !== 'completed')
                        <span class="text-muted fv-mono">To be calculated at checkout</span>
                        <div class="text-muted small mt-1 font-sans" style="font-family: 'Inter', sans-serif;">Your &#8369;{{ number_format($booking->reservation_fee, 2) }} reservation fee already paid will be applied on top of your session cost.</div>
                    @else
                        &#8369;{{ number_format($booking->total_amount, 2) }}
                        @if ($booking->reservation_fee > 0)
                            <div class="text-muted small mt-1" style="font-family: 'Inter', sans-serif;">Includes &#8369;{{ number_format($booking->reservation_fee, 2) }} reservation fee</div>
                        @endif
                    @endif
                </dd>

                @if ($booking->reservation_fee > 0)
                    <dt class="col-sm-4">Reservation Fee</dt>
                    <dd class="col-sm-8">
                        <x-fv-status-dot :color="$booking->reservation_fee_status->color()" :label="$booking->reservation_fee_status->label()" />
                        <div class="text-muted small mt-1">via {{ $booking->reservation_fee_method?->label() }}</div>
                        @if ($booking->reservation_fee_method?->value === 'gcash' && $booking->reservation_fee_reference)
                            <div class="text-muted small">Ref: {{ $booking->reservation_fee_reference }}</div>
                        @endif
                        @if ($booking->reservation_fee_method?->value === 'gcash' && $booking->reservation_fee_paid_at)
                            <div class="text-muted small">Paid {{ $booking->reservation_fee_paid_at->format('M d, Y g:i A') }}</div>
                        @endif
                        @if ($booking->reservation_fee_status->value === 'awaiting_verification')
                            <div class="text-muted small mt-1">We're verifying your GCash payment — this may take a bit before your reservation is approved.</div>
                        @elseif ($booking->reservation_fee_status->value === 'unpaid')
                            <div class="text-muted small mt-1">Please pay this at the counter — your reservation will be approved once received.</div>
                        @endif
                    </dd>

                    <dt class="col-sm-4">Session Payment</dt>
                    <dd class="col-sm-8">
                        <x-fv-status-dot :color="$booking->sessionPaymentColor()" :label="$booking->sessionPaymentLabel()" />
                        <div class="text-muted small mt-1 fv-mono">&#8369;{{ number_format($booking->remainingBalance(), 2) }} via {{ $booking->balance_payment_method?->label() }}</div>
                        @if ($booking->balance_payment_method?->value === 'gcash' && $booking->balance_payment_reference)
                            <div class="text-muted small">Ref: {{ $booking->balance_payment_reference }}</div>
                        @endif
                        @if ($booking->balance_payment_method?->value === 'gcash' && $booking->payment_status->value === 'paid' && $booking->transactions->last())
                            <div class="text-muted small">Paid {{ $booking->transactions->last()->created_at->format('M d, Y g:i A') }}</div>
                        @endif
                        @if ($booking->sessionPaymentAwaitingVerification())
                            <div class="text-muted small mt-1">We're verifying your GCash payment &mdash; your session starts once it's confirmed.</div>
                        @elseif ($booking->payment_status->value === 'unpaid' && ! $isOpenTable)
                            <div class="text-muted small mt-1">Please settle this at the counter &mdash; your session starts once it's received.</div>
                            @if ($booking->balance_payment_method?->value === 'gcash')
                                <a href="{{ route('paymongo.pay-balance', $booking) }}" class="btn btn-sm btn-success mt-2"><i class="bi bi-phone"></i> Pay via GCash Now (Instant)</a>
                            @endif
                        @endif
                    </dd>
                @else
                    <dt class="col-sm-4">Payment</dt>
                    <dd class="col-sm-8"><x-fv-status-dot :color="$booking->payment_status->color()" :label="$booking->payment_status->label()" /></dd>
                @endif

                @if ($isOpenTable && $booking->status->value === 'occupied' && $booking->session_started_at)
                    <dt class="col-sm-4">Session</dt>
                    <dd class="col-sm-8">
                        <span class="badge bg-secondary">In use since {{ $booking->session_started_at->format('g:i A') }}</span>
                        <div class="text-muted small mt-1">Playtime varies per customer &mdash; no fixed end time.</div>
                    </dd>
                @elseif (! $isOpenTable && $booking->status->value === 'occupied' && $booking->session_ends_at)
                    <dt class="col-sm-4">Time Remaining</dt>
                    <dd class="col-sm-8">
                        <span class="badge asset-timer" data-countdown="{{ $booking->session_ends_at->toIso8601String() }}" data-session-start="{{ $booking->session_started_at?->toIso8601String() }}">--:--</span>
                        <div class="text-muted small mt-1">{{ $booking->session_started_at->format('g:i A') }} &ndash; {{ $booking->session_ends_at->format('g:i A') }}</div>
                    </dd>
                @endif

                @if ($booking->cancel_reason)
                    <dt class="col-sm-4">Reason</dt>
                    <dd class="col-sm-8">{{ $booking->cancel_reason }}</dd>
                @endif
            </dl>
        </div>
    </div>
@endsection
