<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@600;700&family=Share+Tech+Mono&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('assets/app.css') }}?v={{ filemtime(public_path('assets/app.css')) }}" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark fv-navbar">
        <div class="container">
            <a class="navbar-brand fv-brand" href="{{ route('home') }}">
                <span class="fv-brand-mark"><i class="bi bi-dice-5-fill"></i></span>
                <span>Famville Koronadal</span>
            </a>
            <div class="ms-auto d-flex align-items-center">
                <a href="#bowling" class="nav-link d-inline-block me-2"><i class="bi bi-align-center"></i> Bowling</a>
                <a href="#billiard" class="nav-link d-inline-block me-3"><i class="bi bi-circle"></i> Billiard</a>
                @auth
                    <a href="{{ route('dashboard') }}" class="btn btn-outline-light btn-sm">Go to Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-outline-light btn-sm me-2">Log In</a>
                    <a href="{{ route('register') }}" class="btn btn-primary btn-sm">Register</a>
                @endauth
            </div>
        </div>
    </nav>

    <header class="fv-hero text-center py-5">
        <div class="container py-5">
            <span class="fv-hero-eyebrow mb-3"><i class="bi bi-dice-5-fill"></i> Koronadal City's Bowling &amp; Billiards Spot</span>
            <h1 class="display-4 mt-3 mb-2">Famville Koronadal</h1>
            <p class="lead">Book a lane or table online, or just walk in &mdash; either way, we've got you covered.</p>
            <p class="text-white-50 mb-4"><i class="bi bi-geo-alt"></i> {{ $contact['address'] }}</p>
            @guest
                <a href="{{ route('register') }}" class="btn btn-primary btn-lg px-4">Book Now</a>
            @endguest
        </div>
    </header>

    <section class="container py-5">
        <div class="text-center mb-4">
            <span class="fv-section-eyebrow">Right Now</span>
            <h2 class="fv-section-title">Live Availability</h2>
        </div>

        <div class="d-flex justify-content-center mb-4">
            <div class="fv-floorplan-switcher" role="group" aria-label="Availability view">
                <button type="button" class="fv-floorplan-switch-btn is-active" id="fvViewListBtn"><i class="bi bi-list-ul"></i> List View</button>
                <button type="button" class="fv-floorplan-switch-btn" id="fvViewFloorPlanBtn"><i class="bi bi-map"></i> Floor Plan</button>
            </div>
        </div>

        <div id="fvListView">
            <h6 id="bowling" class="text-uppercase text-muted mb-3" style="scroll-margin-top: 90px;"><i class="bi bi-align-center"></i> Bowling Lanes</h6>
            <div class="row g-3 mb-4">
                @forelse ($lanes as $lane)
                    @php($closure = \App\Models\AssetClosure::closing($lane, now()->toDateString()))
                    @php($dColor = $closure ? 'danger' : $lane->status->color())
                    @php($dLabel = $closure ? 'Closed' : $lane->status->label())
                    @php($dStatus = $closure ? 'closed' : $lane->status->value)
                    <div class="col-6 col-sm-4 col-lg-3">
                        <div class="card asset-card {{ $dStatus === 'occupied' ? 'fv-facility-card--live' : '' }} border-{{ $dColor }} text-center h-100" role="button" tabindex="0" data-asset-type="lane" data-asset-id="{{ $lane->id }}" data-asset-name="{{ $lane->name }}" data-asset-status="{{ $dStatus }}">
                            <div class="card-body">
                                <i class="bi bi-align-center fs-2"></i>
                                <h5 class="mt-2 mb-1">{{ $lane->name }}</h5>
                                <span class="badge bg-{{ $dColor }}">{{ $dLabel }}</span>
                                @if ($dStatus === 'occupied')
                                    <div class="mt-1"><span class="fv-facility-card-live-tag"><span class="fv-facility-card-live-dot"></span> IN USE</span></div>
                                @endif
                                @if ($closure)
                                    <div class="text-muted small mt-2">{{ $closure->reason }}</div>
                                @else
                                    @php($active = $lane->activeBooking())
                                    @if ($active && $active->session_started_at && $active->session_ends_at)
                                        <div class="mt-2"><span class="badge bg-secondary">{{ $active->session_started_at->format('g:i A') }} &ndash; {{ $active->session_ends_at->format('g:i A') }}</span></div>
                                    @elseif ($active && $active->session_started_at)
                                        <div class="mt-2"><span class="badge bg-secondary">Since {{ $active->session_started_at->format('g:i A') }}</span></div>
                                    @endif
                                @endif
                                <div class="text-muted small mt-2"><i class="bi bi-calendar-week"></i> View timetable</div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12"><p class="text-muted small">No bowling lanes configured yet.</p></div>
                @endforelse
            </div>

            <h6 id="billiard" class="text-uppercase text-muted mb-3" style="scroll-margin-top: 90px;"><i class="bi bi-circle"></i> Billiard Tables</h6>
            <div class="row g-3">
                @forelse ($tables as $table)
                    @php($closure = \App\Models\AssetClosure::closing($table, now()->toDateString()))
                    @php($dColor = $closure ? 'danger' : $table->status->color())
                    @php($dLabel = $closure ? 'Closed' : $table->status->label())
                    @php($dStatus = $closure ? 'closed' : $table->status->value)
                    <div class="col-6 col-sm-4 col-lg-3">
                        <div class="card asset-card {{ $dStatus === 'occupied' ? 'fv-facility-card--live' : '' }} border-{{ $dColor }} text-center h-100" role="button" tabindex="0" data-asset-type="table" data-asset-id="{{ $table->id }}" data-asset-name="{{ $table->name }}" data-asset-status="{{ $dStatus }}">
                            <div class="card-body">
                                <i class="bi bi-circle fs-2"></i>
                                <h5 class="mt-2 mb-1">{{ $table->name }}</h5>
                                <span class="badge bg-{{ $dColor }}">{{ $dLabel }}</span>
                                @if ($dStatus === 'occupied')
                                    <div class="mt-1"><span class="fv-facility-card-live-tag"><span class="fv-facility-card-live-dot"></span> IN USE</span></div>
                                @endif
                                @if ($closure)
                                    <div class="text-muted small mt-2">{{ $closure->reason }}</div>
                                @else
                                    @php($active = $table->activeBooking())
                                    @if ($active && $active->session_ends_at)
                                        <div class="mt-2"><span class="badge asset-timer" data-countdown="{{ $active->session_ends_at->toIso8601String() }}" data-session-start="{{ $active->session_started_at?->toIso8601String() }}">--:--</span></div>
                                    @elseif ($active && $active->session_started_at)
                                        <div class="mt-2"><span class="badge bg-secondary">Since {{ $active->session_started_at->format('g:i A') }}</span></div>
                                    @endif
                                @endif
                                <div class="text-muted small mt-2"><i class="bi bi-calendar-week"></i> View timetable</div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12"><p class="text-muted small">No billiard tables configured yet.</p></div>
                @endforelse
            </div>
        </div>

        <div id="fvFloorPlanView" class="d-none">
            <x-fv-floor-plan :lanes="$lanes" :tables="$tables" mode="guest" />
        </div>

        <p class="text-center text-muted small mt-3">Log in or register to book a lane/table and see live session timers.</p>
    </section>

    <section class="py-5 border-top" style="background-color: var(--fv-surface);">
        <div class="container">
            <div class="text-center mb-4">
                <span class="fv-section-eyebrow">In Pesos</span>
                <h2 class="fv-section-title">Pricing</h2>
            </div>
            <div class="row g-4 justify-content-center">
                <div class="col-md-5">
                    <div class="card fv-pricing-card h-100 shadow-sm">
                        <div class="card-header text-white"><i class="bi bi-align-center"></i> Bowling</div>
                        <ul class="list-group list-group-flush">
                            @foreach ($lanes as $lane)
                                <li class="list-group-item d-flex justify-content-between">
                                    <span>{{ $lane->name }}</span>
                                    <span class="fw-semibold">&#8369;{{ number_format($lane->price_per_scoresheet, 2) }} / scoresheet</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                <div class="col-md-5">
                    <div class="card fv-pricing-card h-100 shadow-sm">
                        <div class="card-header text-white"><i class="bi bi-circle"></i> Billiards</div>
                        <ul class="list-group list-group-flush">
                            @foreach ($rateTiers as $tier)
                                <li class="list-group-item d-flex justify-content-between">
                                    <span>{{ $tier->label }}</span>
                                    <span class="fw-semibold">&#8369;{{ number_format($tier->rate_per_hour, 2) }} / hour</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
            <p class="text-center text-muted small mt-4 mb-0"><i class="bi bi-info-circle"></i> Online reservations include a <strong>10% reservation fee</strong> on top of the session cost (e.g. &#8369;250 scoresheet + 10% reservation fee). Walk-ins pay the listed price only &mdash; no fee.</p>
        </div>
    </section>

    <section class="container py-5">
        <div class="text-center mb-4">
            <span class="fv-section-eyebrow">Why Famville</span>
            <h2 class="fv-section-title">What You Get</h2>
        </div>
        <div class="row g-4">
            <div class="col-sm-6 col-lg-4">
                <div class="card fv-feature-card h-100 shadow-sm"><div class="card-body">
                    <span class="fv-feature-icon mb-2"><i class="bi bi-phone"></i></span>
                    <h5 class="mt-2">Online &amp; Walk-In Booking</h5>
                    <p class="text-muted small mb-0">Reserve a lane or table online, or walk right in — the cashier handles both from one system.</p>
                </div></div>
            </div>
            <div class="col-sm-6 col-lg-4">
                <div class="card fv-feature-card h-100 shadow-sm"><div class="card-body">
                    <span class="fv-feature-icon mb-2"><i class="bi bi-stopwatch"></i></span>
                    <h5 class="mt-2">Live Session Timer &amp; Billing</h5>
                    <p class="text-muted small mb-0">Fees are computed automatically from scoresheets or table time — no manual math, no disputes.</p>
                </div></div>
            </div>
            <div class="col-sm-6 col-lg-4">
                <div class="card fv-feature-card h-100 shadow-sm"><div class="card-body">
                    <span class="fv-feature-icon mb-2"><i class="bi bi-grid-3x3-gap"></i></span>
                    <h5 class="mt-2">Real-Time Availability</h5>
                    <p class="text-muted small mb-0">Color-coded lanes and tables show what's vacant, occupied, reserved, or under maintenance.</p>
                </div></div>
            </div>
            <div class="col-sm-6 col-lg-4">
                <div class="card fv-feature-card h-100 shadow-sm"><div class="card-body">
                    <span class="fv-feature-icon mb-2"><i class="bi bi-shield-lock"></i></span>
                    <h5 class="mt-2">Secure Digital Records</h5>
                    <p class="text-muted small mb-0">Bookings, payments, and history are stored securely — no more paper logs or lost spreadsheets.</p>
                </div></div>
            </div>
            <div class="col-sm-6 col-lg-4">
                <div class="card fv-feature-card h-100 shadow-sm"><div class="card-body">
                    <span class="fv-feature-icon mb-2"><i class="bi bi-bar-chart"></i></span>
                    <h5 class="mt-2">Management Dashboard</h5>
                    <p class="text-muted small mb-0">Revenue, utilization, and peak-hour reporting for smarter business decisions.</p>
                </div></div>
            </div>
            <div class="col-sm-6 col-lg-4">
                <div class="card fv-feature-card h-100 shadow-sm"><div class="card-body">
                    <span class="fv-feature-icon mb-2"><i class="bi bi-cash-coin"></i></span>
                    <h5 class="mt-2">Pay at the Counter</h5>
                    <p class="text-muted small mb-0">Book now, pay later — settle your bill with the cashier once your session is done.</p>
                </div></div>
            </div>
        </div>
    </section>

    <footer class="bg-dark text-white-50 py-5">
        <div class="container text-center">
            <h5 class="text-white">Contact Us</h5>
            <p class="mb-1"><i class="bi bi-geo-alt"></i> {{ $contact['address'] }}</p>
            <p class="mb-1"><i class="bi bi-telephone"></i> {{ $contact['phone'] }} &nbsp; <i class="bi bi-envelope"></i> {{ $contact['email'] }}</p>
            @if ($contact['facebook'])
                <p class="mb-1"><i class="bi bi-facebook"></i> {{ $contact['facebook'] }}</p>
            @endif
            <p class="mb-0"><i class="bi bi-clock"></i> {{ $contact['hours'] }}</p>
            <hr class="border-secondary">
            <small>&copy; {{ date('Y') }} {{ $contact['name'] }}</small>
        </div>
    </footer>

    @include('partials.availability-modal', ['mode' => 'guest'])

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('assets/app.js') }}?v={{ filemtime(public_path('assets/app.js')) }}"></script>
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
</body>
</html>
