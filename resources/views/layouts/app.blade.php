<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Dashboard') — {{ config('app.name') }}</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('assets/app.css') }}" rel="stylesheet">
    @stack('styles')
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid px-3">
            <a class="navbar-brand fv-brand" href="{{ route('dashboard') }}">
                <i class="bi bi-dice-5-fill me-1"></i> Famville Koronadal
                <small>Bowling &amp; Billiards</small>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="mainNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    @auth
                        @if (auth()->user()->isStaff())
                            <li class="nav-item"><a class="nav-link @if(request()->routeIs('staff.dashboard')) active @endif" href="{{ route('staff.dashboard') }}"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                            <li class="nav-item"><a class="nav-link @if(request()->routeIs('staff.lanes.*')) active @endif" href="{{ route('staff.lanes.index') }}"><i class="bi bi-align-center"></i> Lanes</a></li>
                            <li class="nav-item"><a class="nav-link @if(request()->routeIs('staff.billiard-tables.*')) active @endif" href="{{ route('staff.billiard-tables.index') }}"><i class="bi bi-circle"></i> Billiard Tables</a></li>
                            <li class="nav-item"><a class="nav-link @if(request()->routeIs('staff.rate-tiers.*')) active @endif" href="{{ route('staff.rate-tiers.index') }}"><i class="bi bi-tags"></i> Rate Tiers</a></li>
                            <li class="nav-item"><a class="nav-link @if(request()->routeIs('staff.bookings.*')) active @endif" href="{{ route('staff.bookings.index') }}"><i class="bi bi-calendar-check"></i> Bookings</a></li>
                            <li class="nav-item"><a class="nav-link @if(request()->routeIs('staff.pos.*')) active @endif" href="{{ route('staff.pos.index') }}"><i class="bi bi-cash-coin"></i> POS</a></li>
                            <li class="nav-item"><a class="nav-link @if(request()->routeIs('staff.transactions.*')) active @endif" href="{{ route('staff.transactions.index') }}"><i class="bi bi-clock-history"></i> Payment History</a></li>
                            @role('admin')
                                <li class="nav-item"><a class="nav-link @if(request()->routeIs('staff.users.*')) active @endif" href="{{ route('staff.users.index') }}"><i class="bi bi-people"></i> Staff Accounts</a></li>
                                <li class="nav-item"><a class="nav-link @if(request()->routeIs('staff.settings.*')) active @endif" href="{{ route('staff.settings.edit') }}"><i class="bi bi-gear"></i> Settings</a></li>
                            @endrole
                        @else
                            <li class="nav-item"><a class="nav-link @if(request()->routeIs('customer.dashboard')) active @endif" href="{{ route('customer.dashboard') }}"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                            <li class="nav-item"><a class="nav-link @if(request()->routeIs('customer.bookings.create')) active @endif" href="{{ route('customer.bookings.create') }}"><i class="bi bi-plus-circle"></i> Book a Slot</a></li>
                            <li class="nav-item"><a class="nav-link @if(request()->routeIs('customer.bookings.*')) active @endif" href="{{ route('customer.bookings.index') }}"><i class="bi bi-calendar-check"></i> My Bookings</a></li>
                            <li class="nav-item"><a class="nav-link @if(request()->routeIs('customer.transactions.*')) active @endif" href="{{ route('customer.transactions.index') }}"><i class="bi bi-receipt"></i> Billing History</a></li>
                        @endif
                    @endauth
                </ul>
                @auth
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="bi bi-person-circle"></i> {{ auth()->user()->name }}
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="{{ route('profile.edit') }}">Profile</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="dropdown-item">Log Out</button>
                                    </form>
                                </li>
                            </ul>
                        </li>
                    </ul>
                @endauth
            </div>
        </div>
    </nav>

    <div class="container-fluid px-3 px-lg-4 py-4">
        @if (session('status'))
            <div class="alert alert-success alert-dismissible fade show" data-autohide role="alert">
                {{ session('status') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong>Please fix the following:</strong>
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @yield('content')
    </div>

    <footer class="text-center text-muted small py-4">
        &copy; {{ date('Y') }} Famville Koronadal Bowling and Billiards Management System
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('assets/app.js') }}"></script>
    @stack('scripts')
</body>
</html>
