<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Dashboard') — {{ config('app.name') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@600;700&family=Share+Tech+Mono&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('assets/app.css') }}?v={{ filemtime(public_path('assets/app.css')) }}" rel="stylesheet">
    @stack('styles')
</head>
<body>
    <div class="fv-app-shell">
        <aside class="fv-sidebar" id="fvSidebar">
            <a class="fv-sidebar-brand" href="{{ route('dashboard') }}">
                <span class="fv-brand-mark"><i class="bi bi-dice-5-fill"></i></span>
                <span>
                    Famville Koronadal
                    <small>Bowling &amp; Billiards</small>
                </span>
            </a>

            <nav class="fv-sidebar-nav">
                <ul class="list-unstyled mb-0">
                    @auth
                        @if (auth()->user()->isStaff())
                            <li><a class="nav-link @if(request()->routeIs('staff.dashboard')) active @endif" href="{{ route('staff.dashboard') }}"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                            <li>
                                <a class="nav-link @if(request()->routeIs('staff.bookings.*') && request('when') !== 'soon') active @endif" href="{{ route('staff.bookings.index') }}">
                                    <i class="bi bi-calendar-check"></i> Bookings
                                    @if ($newBookingsCount > 0)
                                        <span class="badge rounded-pill bg-danger fv-nav-badge ms-auto">{{ $newBookingsCount }}</span>
                                    @endif
                                </a>
                            </li>
                            <li>
                                <a class="nav-link @if(request()->routeIs('staff.bookings.*') && request('when') === 'soon') active @endif" href="{{ route('staff.bookings.index', ['status' => 'pending', 'when' => 'soon']) }}">
                                    <i class="bi bi-bell"></i> New Booking
                                    @if ($newBookingsCount > 0)
                                        <span class="badge rounded-pill bg-danger fv-nav-badge ms-auto">{{ $newBookingsCount }}</span>
                                    @endif
                                </a>
                            </li>
                            <li><a class="nav-link @if(request()->routeIs('staff.pos.*')) active @endif" href="{{ route('staff.pos.index') }}"><i class="bi bi-cash-coin"></i> POS</a></li>
                            <li><a class="nav-link @if(request()->routeIs('staff.transactions.*')) active @endif" href="{{ route('staff.transactions.index') }}"><i class="bi bi-clock-history"></i> Payment History</a></li>
                            @role('admin')
                                <li><hr class="fv-sidebar-divider"></li>
                                <li><a class="nav-link @if(request()->routeIs('staff.lanes.*')) active @endif" href="{{ route('staff.lanes.index') }}"><i class="bi bi-align-center"></i> Lanes</a></li>
                                <li><a class="nav-link @if(request()->routeIs('staff.billiard-tables.*')) active @endif" href="{{ route('staff.billiard-tables.index') }}"><i class="bi bi-circle"></i> Billiard Tables</a></li>
                                <li><a class="nav-link @if(request()->routeIs('staff.rate-tiers.*')) active @endif" href="{{ route('staff.rate-tiers.index') }}"><i class="bi bi-tags"></i> Rate Tiers</a></li>
                                <li><a class="nav-link @if(request()->routeIs('staff.closures.*')) active @endif" href="{{ route('staff.closures.index') }}"><i class="bi bi-calendar-x"></i> Closures</a></li>
                                <li><a class="nav-link @if(request()->routeIs('staff.users.*')) active @endif" href="{{ route('staff.users.index') }}"><i class="bi bi-people"></i> Staff Accounts</a></li>
                                <li><a class="nav-link @if(request()->routeIs('staff.monthly-archive.*')) active @endif" href="{{ route('staff.monthly-archive.index') }}"><i class="bi bi-archive"></i> Monthly Archive</a></li>
                                <li><a class="nav-link @if(request()->routeIs('staff.settings.*')) active @endif" href="{{ route('staff.settings.edit') }}"><i class="bi bi-gear"></i> Settings</a></li>
                            @endrole
                        @else
                            <li><a class="nav-link @if(request()->routeIs('customer.dashboard')) active @endif" href="{{ route('customer.dashboard') }}"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                            <li><a class="nav-link @if(request()->routeIs('customer.bookings.create')) active @endif" href="{{ route('customer.bookings.create') }}"><i class="bi bi-plus-circle"></i> Book a Slot</a></li>
                            <li><a class="nav-link @if(request()->routeIs('customer.bookings.index', 'customer.bookings.show')) active @endif" href="{{ route('customer.bookings.index') }}"><i class="bi bi-calendar-check"></i> My Bookings</a></li>
                            <li><a class="nav-link @if(request()->routeIs('customer.transactions.*')) active @endif" href="{{ route('customer.transactions.index') }}"><i class="bi bi-receipt"></i> Billing History</a></li>
                        @endif
                    @endauth
                </ul>
            </nav>

            @auth
                <div class="fv-sidebar-footer dropdown">
                    <button class="fv-sidebar-user dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle"></i> <span class="text-truncate">{{ auth()->user()->name }}</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="{{ route('profile.edit') }}">Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            @if (auth()->user()->isCashier())
                                <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#cashCountModal">Log Out</button>
                            @else
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item">Log Out</button>
                                </form>
                            @endif
                        </li>
                    </ul>
                </div>
            @endauth
        </aside>

        <div class="fv-sidebar-backdrop" id="fvSidebarBackdrop"></div>

        <div class="fv-main">
            <header class="fv-topbar d-lg-none">
                <button class="fv-sidebar-toggle" type="button" id="fvSidebarToggle" aria-label="Toggle navigation">
                    <i class="bi bi-list"></i>
                </button>
                <span class="fv-topbar-brand"><i class="bi bi-dice-5-fill"></i> Famville Koronadal</span>
            </header>

            <div class="fv-content container-fluid px-3 px-lg-4 py-4">
                @if (session('status'))
                    <div class="alert alert-success alert-dismissible fade show" data-autohide role="alert">
                        {{ session('status') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if ($errors->any())
                    <script nonce="{{ $cspNonce }}">
                        document.addEventListener('DOMContentLoaded', function () {
                            famvilleShowAlert({{ Js::from('- '.implode("\n- ", $errors->all())) }});
                        });
                    </script>
                @endif

                @yield('content')
            </div>

            <footer class="text-center text-muted small py-4">
                &copy; {{ date('Y') }} Famville Koronadal Bowling and Billiards Management System
            </footer>
        </div>
    </div>

    @auth
        @if (auth()->user()->isCashier())
            @php($fvExpectedCashCount = \App\Services\ReportService::todaySummary(auth()->id())['totalRevenue'])
            <div class="modal fade" id="cashCountModal" tabindex="-1" data-bs-backdrop="static">
                <div class="modal-dialog modal-dialog-scrollable">
                    <div class="modal-content">
                        <form method="POST" action="{{ route('staff.cash-count.logout') }}">
                            @csrf
                            <input type="hidden" id="cc_expected" value="{{ $fvExpectedCashCount }}">
                            <div class="modal-header">
                                <h5 class="modal-title"><i class="bi bi-cash-stack"></i> End-of-Shift Cash Count</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="fv-step mb-3">
                                    <span class="fv-step-num">01</span>
                                    <span class="fv-step-label">Count Your Drawer</span>
                                </div>
                                <p class="text-muted small">Count your cash drawer before logging out. This is emailed to management and cannot be skipped.</p>
                                <table class="table table-sm align-middle mb-3">
                                    <thead>
                                        <tr><th>Denomination</th><th style="width:110px">Quantity</th><th class="text-end">Subtotal</th></tr>
                                    </thead>
                                    <tbody>
                                        @foreach ([1000, 500, 200, 100, 20, 10, 5, 1] as $denom)
                                            <tr>
                                                <td class="fv-mono">&#8369;{{ number_format($denom) }}</td>
                                                <td>
                                                    <input type="number" min="0" max="9999" step="1" name="count_{{ $denom }}" id="cc_{{ $denom }}"
                                                        class="form-control form-control-sm fv-cash-denom" value="0" required
                                                        data-denom="{{ $denom }}">
                                                </td>
                                                <td class="text-end fv-mono" id="cc_sub_{{ $denom }}">&#8369;0.00</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>

                                <x-fv-lane-divider />
                                <div class="fv-step mb-3">
                                    <span class="fv-step-num">02</span>
                                    <span class="fv-step-label">Reconciliation</span>
                                </div>

                                <div class="row g-2 mb-3">
                                    <div class="col-4">
                                        <x-fv-stat-tile label="Expected" value="₱{{ number_format($fvExpectedCashCount, 2) }}" />
                                    </div>
                                    <div class="col-4">
                                        <x-fv-stat-tile label="Counted" value="₱0.00" id="cc_counted_tile" />
                                    </div>
                                    <div class="col-4">
                                        <x-fv-stat-tile label="Difference" value="₱0.00" id="cc_diff_tile" />
                                    </div>
                                </div>

                                <div class="alert d-flex align-items-center gap-2 mb-0" id="cc_reconcile_alert">
                                    <i class="bi bi-check-circle-fill"></i>
                                    <span id="cc_reconcile_text">Start counting above to see your reconciliation status.</span>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-danger"><i class="bi bi-box-arrow-right"></i> Submit Count &amp; Log Out</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    @endauth

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('assets/app.js') }}?v={{ filemtime(public_path('assets/app.js')) }}"></script>
    @stack('scripts')

    <script nonce="{{ $cspNonce }}">
        (function () {
            var sidebar = document.getElementById('fvSidebar');
            var backdrop = document.getElementById('fvSidebarBackdrop');
            var toggle = document.getElementById('fvSidebarToggle');

            function closeSidebar() {
                sidebar.classList.remove('show');
                backdrop.classList.remove('show');
            }

            if (toggle) {
                toggle.addEventListener('click', function () {
                    sidebar.classList.toggle('show');
                    backdrop.classList.toggle('show');
                });
            }
            backdrop.addEventListener('click', closeSidebar);
            sidebar.querySelectorAll('.nav-link').forEach(function (link) {
                link.addEventListener('click', closeSidebar);
            });
        })();
    </script>

    @auth
        @if (auth()->user()->isCashier())
            <script nonce="{{ $cspNonce }}">
                function famvilleFormatPeso(amount) {
                    return '₱' + amount.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                }

                function famvilleUpdateCashCountTotal() {
                    var denoms = [1000, 500, 200, 100, 20, 10, 5, 1];
                    var total = 0;
                    denoms.forEach(function (denom) {
                        var input = document.getElementById('cc_' + denom);
                        var qty = Math.max(0, parseInt(input.value, 10) || 0);
                        var subtotal = qty * denom;
                        total += subtotal;
                        document.getElementById('cc_sub_' + denom).textContent = famvilleFormatPeso(subtotal);
                    });

                    var expected = parseFloat(document.getElementById('cc_expected').value) || 0;
                    var difference = Math.round((total - expected) * 100) / 100;

                    document.querySelector('#cc_counted_tile .fv-stat-tile-value').textContent = famvilleFormatPeso(total);
                    document.querySelector('#cc_diff_tile .fv-stat-tile-value').textContent = (difference > 0 ? '+' : '') + famvilleFormatPeso(difference);

                    var alertBox = document.getElementById('cc_reconcile_alert');
                    var alertText = document.getElementById('cc_reconcile_text');
                    var isBalanced = difference === 0;

                    alertBox.classList.remove('alert-success', 'alert-warning', 'alert-secondary');
                    alertBox.classList.add(total === 0 ? 'alert-secondary' : (isBalanced ? 'alert-success' : 'alert-warning'));
                    alertBox.querySelector('i').className = 'bi ' + (total === 0 ? 'bi-info-circle' : (isBalanced ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill'));
                    alertText.textContent = total === 0
                        ? 'Start counting above to see your reconciliation status.'
                        : (isBalanced ? 'Balanced — counted matches expected.' : 'Variance detected — ' + famvilleFormatPeso(Math.abs(difference)) + (difference > 0 ? ' over' : ' short') + '.');
                }

                document.querySelectorAll('.fv-cash-denom').forEach(function (input) {
                    input.addEventListener('input', famvilleUpdateCashCountTotal);
                });
            </script>
        @endif

        @if (auth()->user()->isStaff())
            <div id="fvNotifContainer" class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1080;" aria-live="polite" aria-atomic="true"></div>

            <script nonce="{{ $cspNonce }}">
                (function () {
                    // Polled instead of pushed (no websocket/queue worker
                    // infra in this app) — cheap enough for this venue's
                    // volume, and it's what lets a new online reservation
                    // show up as a toast within seconds on whichever
                    // staff/admin page the cashier already has open,
                    // instead of needing a manual refresh.
                    var pollUrl = {{ Js::from(route('staff.bookings.notifications')) }};
                    var bookingsUrl = {{ Js::from(route('staff.bookings.index', ['status' => 'pending'])) }};
                    var since = new Date().toISOString();

                    function showNotifToast(booking) {
                        var container = document.getElementById('fvNotifContainer');
                        var toastEl = document.createElement('div');
                        toastEl.className = 'toast';
                        toastEl.setAttribute('role', 'alert');
                        toastEl.innerHTML =
                            '<div class="toast-header">' +
                                '<i class="bi bi-bell-fill text-primary me-2"></i>' +
                                '<strong class="me-auto">New Online Booking</strong>' +
                                '<button type="button" class="btn-close" data-bs-dismiss="toast"></button>' +
                            '</div>' +
                            '<div class="toast-body">' +
                                '<div class="fw-semibold">' + booking.customer_name + '</div>' +
                                '<div class="text-muted small mb-2">' + booking.asset_label + ' &middot; ' + booking.booking_date + ' ' + booking.start_time + ' &middot; <span class="fv-ref">' + booking.booking_no + '</span></div>' +
                                '<a href="' + bookingsUrl + '" class="btn btn-sm btn-primary">View Booking</a>' +
                            '</div>';
                        container.appendChild(toastEl);
                        var toast = new bootstrap.Toast(toastEl, { delay: 15000 });
                        toastEl.addEventListener('hidden.bs.toast', function () { toastEl.remove(); });
                        toast.show();
                    }

                    function pollNotifications() {
                        fetch(pollUrl + '?since=' + encodeURIComponent(since), { headers: { 'Accept': 'application/json' } })
                            .then(function (response) { return response.ok ? response.json() : null; })
                            .then(function (data) {
                                if (!data) {
                                    return;
                                }
                                since = data.server_time;
                                data.new.forEach(showNotifToast);
                            })
                            .catch(function () { /* silent — next poll just tries again */ });
                    }

                    setInterval(pollNotifications, 15000);
                })();
            </script>
        @endif
    @endauth
</body>
</html>
