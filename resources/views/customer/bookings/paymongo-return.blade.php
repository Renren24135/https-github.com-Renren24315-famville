@extends('layouts.app')

@section('title', 'Confirming Payment')

@section('content')
    <div class="card shadow-sm" style="max-width: 500px; margin: 0 auto;">
        <div class="card-body text-center py-5">
            @if ($failed)
                <i class="bi bi-x-circle fs-1 text-danger d-block mb-3"></i>
                <h4 class="mb-2">Payment Not Completed</h4>
                <p class="text-muted mb-4">Your GCash payment didn't go through. Nothing was charged — you can try again from your booking.</p>
            @else
                <div class="spinner-border text-primary mb-3" role="status"></div>
                <h4 class="mb-2">Confirming Your Payment&hellip;</h4>
                <p class="text-muted mb-4">We're verifying your GCash payment with PayMongo &mdash; this usually takes just a few seconds. Your booking will update automatically once it's confirmed.</p>
            @endif

            <a href="{{ route('customer.bookings.show', $booking) }}" class="btn btn-primary"><i class="bi bi-receipt"></i> View Booking</a>
        </div>
    </div>
@endsection
