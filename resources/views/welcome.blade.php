<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }}</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('assets/app.css') }}" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand fv-brand" href="{{ route('home') }}"><i class="bi bi-dice-5-fill me-1"></i> Famville Koronadal</a>
            <div class="ms-auto">
                @auth
                    <a href="{{ route('dashboard') }}" class="btn btn-outline-light btn-sm">Go to Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-outline-light btn-sm me-2">Log In</a>
                    <a href="{{ route('register') }}" class="btn btn-primary btn-sm">Register</a>
                @endauth
            </div>
        </div>
    </nav>

    <header class="bg-dark text-white text-center py-5">
        <div class="container py-4">
            <h1 class="display-5 fw-bold"><i class="bi bi-dice-5-fill"></i> Famville Koronadal</h1>
            <p class="lead">Bowling and Billiards Management System</p>
            <p class="text-white-50">{{ $contact['address'] }}</p>
            @guest
                <a href="{{ route('register') }}" class="btn btn-primary btn-lg mt-2">Book Now</a>
            @endguest
        </div>
    </header>

    <section class="container py-5">
        <h2 class="text-center mb-4">Live Availability</h2>

        <h6 class="text-uppercase text-muted mb-3"><i class="bi bi-align-center"></i> Bowling Lanes</h6>
        <div class="row g-3 mb-4">
            @forelse ($lanes as $lane)
                <div class="col-6 col-sm-4 col-lg-3">
                    <div class="card asset-card border-{{ $lane->status->color() }} text-center h-100">
                        <div class="card-body">
                            <i class="bi bi-align-center fs-2"></i>
                            <h5 class="mt-2 mb-1">{{ $lane->name }}</h5>
                            <span class="badge bg-{{ $lane->status->color() }}">{{ $lane->status->label() }}</span>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12"><p class="text-muted small">No bowling lanes configured yet.</p></div>
            @endforelse
        </div>

        <h6 class="text-uppercase text-muted mb-3"><i class="bi bi-circle"></i> Billiard Tables</h6>
        <div class="row g-3">
            @forelse ($tables as $table)
                <div class="col-6 col-sm-4 col-lg-3">
                    <div class="card asset-card border-{{ $table->status->color() }} text-center h-100">
                        <div class="card-body">
                            <i class="bi bi-circle fs-2"></i>
                            <h5 class="mt-2 mb-1">{{ $table->name }}</h5>
                            <span class="badge bg-{{ $table->status->color() }}">{{ $table->status->label() }}</span>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12"><p class="text-muted small">No billiard tables configured yet.</p></div>
            @endforelse
        </div>

        <p class="text-center text-muted small mt-3">Log in or register to book a lane/table and see live session timers.</p>
    </section>

    <section class="bg-white py-5 border-top">
        <div class="container">
            <h2 class="text-center mb-4">Pricing</h2>
            <div class="row g-4 justify-content-center">
                <div class="col-md-5">
                    <div class="card h-100 shadow-sm">
                        <div class="card-header bg-dark text-white"><i class="bi bi-align-center"></i> Bowling</div>
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
                    <div class="card h-100 shadow-sm">
                        <div class="card-header bg-dark text-white"><i class="bi bi-circle"></i> Billiards</div>
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
        </div>
    </section>

    <section class="container py-5">
        <h2 class="text-center mb-4">What You Get</h2>
        <div class="row g-4">
            <div class="col-sm-6 col-lg-4">
                <div class="card h-100 shadow-sm"><div class="card-body">
                    <i class="bi bi-phone fs-2 text-primary"></i>
                    <h5 class="mt-2">Online &amp; Walk-In Booking</h5>
                    <p class="text-muted small mb-0">Reserve a lane or table online, or walk right in — the cashier handles both from one system.</p>
                </div></div>
            </div>
            <div class="col-sm-6 col-lg-4">
                <div class="card h-100 shadow-sm"><div class="card-body">
                    <i class="bi bi-stopwatch fs-2 text-primary"></i>
                    <h5 class="mt-2">Live Session Timer &amp; Billing</h5>
                    <p class="text-muted small mb-0">Fees are computed automatically from scoresheets or table time — no manual math, no disputes.</p>
                </div></div>
            </div>
            <div class="col-sm-6 col-lg-4">
                <div class="card h-100 shadow-sm"><div class="card-body">
                    <i class="bi bi-grid-3x3-gap fs-2 text-primary"></i>
                    <h5 class="mt-2">Real-Time Availability</h5>
                    <p class="text-muted small mb-0">Color-coded lanes and tables show what's vacant, occupied, reserved, or under maintenance.</p>
                </div></div>
            </div>
            <div class="col-sm-6 col-lg-4">
                <div class="card h-100 shadow-sm"><div class="card-body">
                    <i class="bi bi-shield-lock fs-2 text-primary"></i>
                    <h5 class="mt-2">Secure Digital Records</h5>
                    <p class="text-muted small mb-0">Bookings, payments, and history are stored securely — no more paper logs or lost spreadsheets.</p>
                </div></div>
            </div>
            <div class="col-sm-6 col-lg-4">
                <div class="card h-100 shadow-sm"><div class="card-body">
                    <i class="bi bi-bar-chart fs-2 text-primary"></i>
                    <h5 class="mt-2">Management Dashboard</h5>
                    <p class="text-muted small mb-0">Revenue, utilization, and peak-hour reporting for smarter business decisions.</p>
                </div></div>
            </div>
            <div class="col-sm-6 col-lg-4">
                <div class="card h-100 shadow-sm"><div class="card-body">
                    <i class="bi bi-cash-coin fs-2 text-primary"></i>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
