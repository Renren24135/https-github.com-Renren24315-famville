@extends('layouts.app')

@section('title', 'Dashboard')

@php
    $hour = now()->hour;
    $greeting = $hour < 12 ? 'Good Morning' : ($hour < 18 ? 'Good Afternoon' : 'Good Evening');
    // Status alone isn't enough — a lane can be "available" on paper but
    // closed today for a tournament/maintenance, so it still shouldn't
    // count as actually available to book right now.
    $availableLanes = $lanes->filter(fn ($lane) => $lane->status->value === 'available' && ! \App\Models\AssetClosure::closing($lane, now()->toDateString()))->count();
    $availableTables = $tables->filter(fn ($table) => $table->status->value === 'available' && ! \App\Models\AssetClosure::closing($table, now()->toDateString()))->count();

    // The customer's own upcoming/in-progress session, if any — a direct
    // query here (not a controller change) mirrors the pattern already
    // used by $lane->activeBooking()/$table->activeBooking() below.
    $nextSession = auth()->user()->bookings()
        ->whereIn('status', ['pending', 'approved', 'occupied'])
        ->where('booking_date', '>=', now()->toDateString())
        ->with('bookable')
        ->orderBy('booking_date')
        ->orderBy('start_time')
        ->first();
@endphp

@section('content')
    <div data-auto-refresh="30"></div>

    <div class="fv-dash-hero">
        <div class="row align-items-center g-4">
            <div class="col-lg-7">
                <div class="fv-dash-hero-eyebrow"><i class="bi bi-dice-5-fill"></i> {{ $greeting }}, {{ auth()->user()->name }}</div>
                <h1>Ready for a game?</h1>

                <div class="row g-3 mb-3" style="max-width: 420px;">
                    <div class="col-6">
                        <div class="fv-stat-tile fv-stat-tile--accent">
                            <div class="fv-stat-tile-label"><i class="bi bi-align-center"></i> Bowling</div>
                            <div class="fv-stat-tile-value">{{ $availableLanes }}<span class="fs-6 text-muted">/{{ $lanes->count() }}</span></div>
                            <div class="fv-stat-tile-sub">lanes available</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="fv-stat-tile fv-stat-tile--accent">
                            <div class="fv-stat-tile-label"><i class="bi bi-circle"></i> Billiards</div>
                            <div class="fv-stat-tile-value">{{ $availableTables }}<span class="fs-6 text-muted">/{{ $tables->count() }}</span></div>
                            <div class="fv-stat-tile-sub">tables available</div>
                        </div>
                    </div>
                </div>

                <a href="{{ route('customer.bookings.create') }}" class="btn btn-primary btn-lg"><i class="bi bi-plus-circle"></i> Book a Session</a>
            </div>

            <div class="col-lg-5">
                <div class="fv-next-session">
                    <div class="fv-eyebrow mb-2"><i class="bi bi-calendar-check"></i> Your Next Session</div>
                    @if ($nextSession)
                        @php($isLane = $nextSession->bookable_type === \App\Models\Lane::class)
                        <h3 class="fv-booking-card-title mb-1">{{ $nextSession->bookable->name ?? $nextSession->assetLabel() }}</h3>
                        <div class="text-muted small mb-2">
                            {{ $nextSession->booking_date->isToday() ? 'Today' : $nextSession->booking_date->format('M d, Y') }}
                            &middot; {{ \Illuminate\Support\Carbon::parse($nextSession->start_time)->format('g:i A') }}
                        </div>
                        <x-fv-status-dot :color="$nextSession->status->color()" :label="$nextSession->status->label()" />
                        <div class="mt-3">
                            <a href="{{ route('customer.bookings.show', $nextSession) }}" class="btn btn-sm btn-outline-primary w-100">View Booking</a>
                        </div>
                    @else
                        <p class="text-muted small mb-3">Your next game is waiting.</p>
                        <a href="{{ route('customer.bookings.create') }}" class="btn btn-sm btn-primary w-100"><i class="bi bi-plus-circle"></i> Book a Session</a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
        <h2 class="fv-section-title h5 mb-0"><i class="bi bi-grid-3x3-gap"></i> Venue Status</h2>
        <div class="fv-floorplan-switcher" role="group" aria-label="Venue view">
            <button type="button" class="fv-floorplan-switch-btn is-active" id="fvViewListBtn"><i class="bi bi-list-ul"></i> List View</button>
            <button type="button" class="fv-floorplan-switch-btn" id="fvViewFloorPlanBtn"><i class="bi bi-map"></i> Floor Plan</button>
        </div>
    </div>

    <div id="fvListView">
        <h6 class="text-uppercase text-muted mb-3"><i class="bi bi-align-center"></i> Bowling Lanes</h6>
        <div class="row g-3 mb-5">
            @forelse ($lanes as $lane)
                <div class="col-6 col-sm-4 col-lg-3">
                    <x-fv-facility-card :asset="$lane" type="lane" icon="bi-align-center" />
                </div>
            @empty
                <div class="col-12"><p class="text-muted small">No bowling lanes configured yet.</p></div>
            @endforelse
        </div>

        <h6 class="text-uppercase text-muted mb-3"><i class="bi bi-circle"></i> Billiard Tables</h6>
        <div class="row g-3 mb-5">
            @forelse ($tables as $table)
                <div class="col-6 col-sm-4 col-lg-3">
                    <x-fv-facility-card :asset="$table" type="table" icon="bi-circle" />
                </div>
            @empty
                <div class="col-12"><p class="text-muted small">No billiard tables configured yet.</p></div>
            @endforelse
        </div>
    </div>

    <div id="fvFloorPlanView" class="d-none mb-5">
        <x-fv-floor-plan :lanes="$lanes" :tables="$tables" mode="customer" />
    </div>

    <x-fv-lane-divider />

    <div class="row g-4">
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header"><i class="bi bi-tag"></i> Pricing</div>
                <div class="card-body">
                    <h6>Bowling (per scoresheet)</h6>
                    <ul class="list-group list-group-flush mb-3">
                        @foreach ($lanes as $lane)
                            <li class="list-group-item d-flex justify-content-between px-0 bg-transparent">
                                <span>{{ $lane->name }}</span>
                                <span class="fw-semibold fv-mono">&#8369;{{ number_format($lane->price_per_scoresheet, 2) }}</span>
                            </li>
                        @endforeach
                    </ul>
                    <h6>Billiards (per hour)</h6>
                    <ul class="list-group list-group-flush mb-3">
                        @foreach ($rateTiers as $tier)
                            <li class="list-group-item d-flex justify-content-between px-0 bg-transparent">
                                <span>{{ $tier->label }}</span>
                                <span class="fw-semibold fv-mono">&#8369;{{ number_format($tier->rate_per_hour, 2) }}</span>
                            </li>
                        @endforeach
                    </ul>
                    <p class="text-muted small mb-0"><i class="bi bi-info-circle"></i> Online bookings include a <strong>10% reservation fee</strong> on top of the price shown (e.g. &#8369;250 scoresheet + 10% reservation fee). Walk-ins pay the listed price only.</p>
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

    @include('partials.availability-modal', ['mode' => 'customer'])
@endsection

@push('scripts')
<script nonce="{{ $cspNonce }}">
    document.addEventListener('DOMContentLoaded', function () {
        var listBtn = document.getElementById('fvViewListBtn');
        var planBtn = document.getElementById('fvViewFloorPlanBtn');
        var listView = document.getElementById('fvListView');
        var planView = document.getElementById('fvFloorPlanView');

        listBtn.addEventListener('click', function () {
            listBtn.classList.add('is-active');
            planBtn.classList.remove('is-active');
            listView.classList.remove('d-none');
            planView.classList.add('d-none');
        });
        planBtn.addEventListener('click', function () {
            planBtn.classList.add('is-active');
            listBtn.classList.remove('is-active');
            planView.classList.remove('d-none');
            listView.classList.add('d-none');
        });
    });
</script>
@endpush
