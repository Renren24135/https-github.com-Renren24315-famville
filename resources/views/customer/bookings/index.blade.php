@extends('layouts.app')

@section('title', 'My Bookings')

@section('content')
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <h3 class="mb-0"><i class="bi bi-calendar-check"></i> My Bookings</h3>
        <a href="{{ route('customer.bookings.create') }}" class="btn btn-primary"><i class="bi bi-plus-circle"></i> New Booking</a>
    </div>

    <div class="btn-group mb-4" role="group" aria-label="Filter by asset type">
        @foreach (['all' => 'All', 'lane' => 'Bowling', 'table' => 'Billiards'] as $value => $label)
            <a href="{{ route('customer.bookings.index', ['type' => $value]) }}"
               class="btn btn-sm {{ $type === $value ? 'btn-primary' : 'btn-outline-secondary' }}">{{ $label }}</a>
        @endforeach
    </div>

    @if ($bookings->isEmpty())
        <div class="card shadow-sm">
            <div class="card-body text-center py-5">
                <i class="bi bi-dice-5 fs-1 text-muted d-block mb-3"></i>
                <p class="mb-3">Your next game is waiting.</p>
                <a href="{{ route('customer.bookings.create') }}" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Book a Session</a>
            </div>
        </div>
    @else
        <div class="row g-3">
            @foreach ($bookings as $booking)
                <div class="col-sm-6 col-lg-4">
                    <x-fv-booking-card :booking="$booking" />
                </div>
            @endforeach
        </div>

        <div class="mt-4">{{ $bookings->links() }}</div>
    @endif
@endsection
