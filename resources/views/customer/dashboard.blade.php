@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div data-auto-refresh="30"></div>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0"><i class="bi bi-speedometer2"></i> Welcome, {{ auth()->user()->name }}</h3>
        <a href="{{ route('customer.bookings.create') }}" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Book a Slot</a>
    </div>

    <h5 class="mb-3"><i class="bi bi-align-center"></i> Bowling Lanes</h5>
    <div class="row g-3 mb-4">
        @forelse ($lanes as $lane)
            <div class="col-6 col-sm-4 col-lg-3">
                <div class="card asset-card border-{{ $lane->status->color() }} text-center h-100">
                    <div class="card-body">
                        <span class="status-dot bg-{{ $lane->status->color() }}"></span>
                        <i class="bi bi-align-center fs-2 d-block mt-2"></i>
                        <h5 class="mt-2 mb-1">{{ $lane->name }}</h5>
                        <span class="badge bg-{{ $lane->status->color() }} mb-2">{{ $lane->status->label() }}</span>
                        <div class="text-muted small">&#8369;{{ number_format($lane->price_per_scoresheet, 2) }} / scoresheet</div>
                        @php($active = $lane->activeBooking())
                        @if ($active && $active->session_ends_at)
                            <span class="badge asset-timer mt-2" data-countdown="{{ $active->session_ends_at->toIso8601String() }}" data-session-start="{{ $active->session_started_at?->toIso8601String() }}">--:--</span>
                            <div class="text-muted small mt-1">time remaining</div>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12"><p class="text-muted small">No bowling lanes configured yet.</p></div>
        @endforelse
    </div>

    <h5 class="mb-3"><i class="bi bi-circle"></i> Billiard Tables</h5>
    <div class="row g-3 mb-5">
        @forelse ($tables as $table)
            <div class="col-6 col-sm-4 col-lg-3">
                <div class="card asset-card border-{{ $table->status->color() }} text-center h-100">
                    <div class="card-body">
                        <span class="status-dot bg-{{ $table->status->color() }}"></span>
                        <i class="bi bi-circle fs-2 d-block mt-2"></i>
                        <h5 class="mt-2 mb-1">{{ $table->name }}</h5>
                        <span class="badge bg-{{ $table->status->color() }} mb-2">{{ $table->status->label() }}</span>
                        @php($active = $table->activeBooking())
                        @if ($active && $active->session_ends_at)
                            <span class="badge asset-timer mt-2" data-countdown="{{ $active->session_ends_at->toIso8601String() }}" data-session-start="{{ $active->session_started_at?->toIso8601String() }}">--:--</span>
                            <div class="text-muted small mt-1">time remaining</div>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12"><p class="text-muted small">No billiard tables configured yet.</p></div>
        @endforelse
    </div>

    <div class="row g-4">
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header"><i class="bi bi-tag"></i> Pricing</div>
                <div class="card-body">
                    <h6>Bowling (per scoresheet)</h6>
                    <ul class="list-group list-group-flush mb-3">
                        @foreach ($lanes as $lane)
                            <li class="list-group-item d-flex justify-content-between px-0">
                                <span>{{ $lane->name }}</span>
                                <span class="fw-semibold">&#8369;{{ number_format($lane->price_per_scoresheet, 2) }}</span>
                            </li>
                        @endforeach
                    </ul>
                    <h6>Billiards (per hour)</h6>
                    <ul class="list-group list-group-flush">
                        @foreach ($rateTiers as $tier)
                            <li class="list-group-item d-flex justify-content-between px-0">
                                <span>{{ $tier->label }}</span>
                                <span class="fw-semibold">&#8369;{{ number_format($tier->rate_per_hour, 2) }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header"><i class="bi bi-telephone"></i> Contact Us</div>
                <div class="card-body">
                    <p class="mb-1"><i class="bi bi-shop"></i> {{ $contact['name'] }}</p>
                    <p class="mb-1"><i class="bi bi-geo-alt"></i> {{ $contact['address'] }}</p>
                    <p class="mb-1"><i class="bi bi-telephone"></i> {{ $contact['phone'] }}</p>
                    <p class="mb-1"><i class="bi bi-envelope"></i> {{ $contact['email'] }}</p>
                    @if ($contact['facebook'])
                        <p class="mb-1"><i class="bi bi-facebook"></i> {{ $contact['facebook'] }}</p>
                    @endif
                    <p class="mb-0"><i class="bi bi-clock"></i> {{ $contact['hours'] }}</p>
                </div>
            </div>
        </div>
    </div>
@endsection
