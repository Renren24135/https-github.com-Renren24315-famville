@extends('layouts.app')

@section('title', 'Booking Details')

@section('content')
    <h3 class="mb-4"><i class="bi bi-calendar-check"></i> Booking Details</h3>

    <div class="card shadow-sm" style="max-width: 600px;">
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-4">Asset</dt>
                <dd class="col-sm-8">{{ $booking->assetLabel() }}</dd>

                <dt class="col-sm-4">Date</dt>
                <dd class="col-sm-8">{{ $booking->booking_date->format('F d, Y') }}</dd>

                <dt class="col-sm-4">Start Time</dt>
                <dd class="col-sm-8">{{ \Illuminate\Support\Carbon::parse($booking->start_time)->format('g:i A') }}</dd>

                <dt class="col-sm-4">Quantity</dt>
                <dd class="col-sm-8">{{ $booking->scoresheets ? $booking->scoresheets.' scoresheet(s)' : $booking->hours.' hour(s)' }}</dd>

                <dt class="col-sm-4">Status</dt>
                <dd class="col-sm-8"><span class="badge bg-{{ $booking->status->color() }}">{{ $booking->status->label() }}</span></dd>

                <dt class="col-sm-4">Amount</dt>
                <dd class="col-sm-8">&#8369;{{ number_format($booking->total_amount, 2) }}</dd>

                <dt class="col-sm-4">Payment</dt>
                <dd class="col-sm-8"><span class="badge bg-{{ $booking->payment_status->color() }}">{{ $booking->payment_status->label() }}</span></dd>

                @if ($booking->session_ends_at)
                    <dt class="col-sm-4">Time Remaining</dt>
                    <dd class="col-sm-8">
                        <span class="badge asset-timer" data-countdown="{{ $booking->session_ends_at->toIso8601String() }}" data-session-start="{{ $booking->session_started_at?->toIso8601String() }}">--:--</span>
                    </dd>
                @endif

                @if ($booking->cancel_reason)
                    <dt class="col-sm-4">Reason</dt>
                    <dd class="col-sm-8">{{ $booking->cancel_reason }}</dd>
                @endif
            </dl>
        </div>
        <div class="card-footer">
            <a href="{{ route('customer.bookings.index') }}" class="btn btn-outline-secondary btn-sm">Back to My Bookings</a>
        </div>
    </div>
@endsection
