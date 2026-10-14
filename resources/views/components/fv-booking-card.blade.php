@props(['booking'])

@php
    $isLane = $booking->bookable_type === \App\Models\Lane::class;
    $qtyLabel = match (true) {
        $booking->scoresheets !== null => $booking->scoresheets.' scoresheet(s)',
        $booking->hours !== null => $booking->hours.' hour(s)',
        default => 'Open Time',
    };
@endphp

<div {{ $attributes->class(['fv-booking-card', 'fv-booking-card--'.$booking->status->color()]) }}>
    <div class="fv-booking-card-eyebrow"><i class="bi {{ $isLane ? 'bi-align-center' : 'bi-circle' }}"></i> {{ $isLane ? 'Bowling' : 'Billiards' }}</div>
    <h3 class="fv-booking-card-title">{{ $booking->bookable->name ?? $booking->assetLabel() }}</h3>
    <div class="fv-booking-card-when">
        {{ $booking->booking_date->format('M d, Y') }} &middot; {{ \Illuminate\Support\Carbon::parse($booking->start_time)->format('g:i A') }}
        <span class="text-muted">&middot; {{ $qtyLabel }}</span>
    </div>

    <div class="fv-booking-card-amount">&#8369;{{ number_format($booking->total_amount, 2) }}</div>

    <div class="d-flex flex-wrap gap-2">
        <x-fv-status-dot :color="$booking->status->color()" :label="$booking->status->label()" />
        <x-fv-status-dot :color="$booking->payment_status->color()" :label="$booking->payment_status->label()" />
    </div>

    <div class="fv-booking-card-ref">{{ $booking->bookingNo() }}</div>

    <div class="fv-booking-card-foot">
        <a href="{{ route('customer.bookings.show', $booking) }}" class="btn btn-sm btn-outline-secondary">View Details</a>
        @if (in_array($booking->status->value, ['pending', 'approved']))
            <form method="POST" action="{{ route('customer.bookings.cancel', $booking) }}" data-confirm="Cancel this booking?">
                @csrf
                <button class="btn btn-sm btn-outline-danger">Cancel</button>
            </form>
        @endif
    </div>
</div>
