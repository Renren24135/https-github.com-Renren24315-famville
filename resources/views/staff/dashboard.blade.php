@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <div class="fv-eyebrow mb-1">{{ now()->format('l, F d') }}</div>
            <h3 class="mb-0"><i class="bi bi-speedometer2"></i> {{ $isAdmin ? 'Executive Dashboard' : 'My Dashboard' }}</h3>
        </div>
        @can('send-reports')
            <form method="POST" action="{{ route('staff.dashboard.email-report') }}">
                @csrf
                <button type="submit" class="btn btn-outline-primary btn-sm"><i class="bi bi-envelope-paper"></i> Email Report Now</button>
            </form>
        @endcan
    </div>
    @error('report')<div class="alert alert-danger">{{ $message }}</div>@enderror

    <div class="row g-3 mb-2">
        <div class="col-6 col-md-3">
            <x-fv-stat-tile :label="$isAdmin ? \"Today's Revenue\" : 'Your Sales Today'" :value="'₱'.number_format($todayRevenue, 2)" accent />
        </div>
        <div class="col-6 col-md-3">
            <x-fv-stat-tile :label="$isAdmin ? 'Transactions Today' : 'Your Transactions'" :value="$todayTransactionCount" />
        </div>
        <div class="col-6 col-md-3">
            <x-fv-stat-tile label="Pending Approvals" :value="$pendingCount" />
        </div>
        <div class="col-6 col-md-3">
            <x-fv-stat-tile label="Assets Occupied" :value="$occupiedCount.' / '.$totalAssets" />
        </div>
    </div>
    <p class="text-muted small mb-4"><i class="bi bi-info-circle"></i> "Today" figures reset automatically at midnight &mdash; no records are ever deleted, and full history stays available in Payment History or your emailed reports.</p>

    <div class="row g-4 mb-4">
        <div class="col-lg-7">
            <div class="d-flex align-items-center justify-content-between mb-2 flex-wrap gap-2">
                <h2 class="h6 mb-0"><i class="bi bi-broadcast"></i> Live Venue Status</h2>
                <div class="d-flex align-items-center gap-2">
                    <div class="fv-floorplan-switcher" role="group" aria-label="Venue view">
                        <button type="button" class="fv-floorplan-switch-btn is-active" id="fvViewListBtn"><i class="bi bi-list-ul"></i> List</button>
                        <button type="button" class="fv-floorplan-switch-btn" id="fvViewFloorPlanBtn"><i class="bi bi-map"></i> Floor Plan</button>
                    </div>
                    <a href="{{ route('staff.bookings.index') }}" class="small text-decoration-none">Full bookings &rarr;</a>
                </div>
            </div>
            <div class="card shadow-sm">
                <div class="card-body">
                    <div id="fvListView">
                        <div class="fv-eyebrow mb-2"><i class="bi bi-align-center"></i> Bowling</div>
                        <div class="row g-2 mb-3">
                            @forelse ($lanes as $lane)
                                <div class="col-6 col-md-4">
                                    <x-fv-facility-card :asset="$lane" type="lane" icon="bi-align-center" />
                                </div>
                            @empty
                                <div class="col-12"><p class="text-muted small mb-0">No lanes configured.</p></div>
                            @endforelse
                        </div>
                        <div class="fv-eyebrow mb-2"><i class="bi bi-circle"></i> Billiards</div>
                        <div class="row g-2">
                            @forelse ($tables as $table)
                                <div class="col-6 col-md-4">
                                    <x-fv-facility-card :asset="$table" type="table" icon="bi-circle" />
                                </div>
                            @empty
                                <div class="col-12"><p class="text-muted small mb-0">No tables configured.</p></div>
                            @endforelse
                        </div>
                    </div>
                    <div id="fvFloorPlanView" class="d-none">
                        <x-fv-floor-plan :lanes="$lanes" :tables="$tables" mode="staff" />
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <h2 class="h6 mb-0"><i class="bi bi-bell"></i> Pending Bookings</h2>
                <a href="{{ route('staff.bookings.index', ['status' => 'pending']) }}" class="small text-decoration-none">View all &rarr;</a>
            </div>
            <div class="card shadow-sm h-100">
                <div class="list-group list-group-flush">
                    @forelse ($pendingBookings as $booking)
                        <div class="list-group-item bg-transparent">
                            <div class="d-flex justify-content-between align-items-start gap-2">
                                <div>
                                    <div class="fw-semibold">{{ $booking->customer_name }}</div>
                                    <div class="text-muted small">{{ $booking->assetLabel() }} &middot; {{ $booking->booking_date->format('M d') }} {{ \Illuminate\Support\Carbon::parse($booking->start_time)->format('g:i A') }}</div>
                                </div>
                                <span class="badge bg-{{ $booking->source->value === 'online' ? 'info' : 'dark' }}">{{ $booking->source->label() }}</span>
                            </div>
                            <a href="{{ route('staff.bookings.index', ['status' => 'pending']) }}" class="btn btn-sm btn-outline-primary mt-2">Review &amp; Act</a>
                        </div>
                    @empty
                        <div class="list-group-item bg-transparent text-muted text-center py-4">Nothing pending &mdash; all caught up.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <x-fv-lane-divider />

    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header">Bowling Revenue — Last 7 Days</div>
                <div class="card-body"><canvas id="revenueChartLane" height="120"></canvas></div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header">Billiards Revenue — Last 7 Days</div>
                <div class="card-body"><canvas id="revenueChartTable" height="120"></canvas></div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header">Bowling Peak Hours Today</div>
                <div class="card-body"><canvas id="peakChartLane" height="120"></canvas></div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header">Billiards Peak Hours Today</div>
                <div class="card-body"><canvas id="peakChartTable" height="120"></canvas></div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header">Lane Utilization (sessions)</div>
                <ul class="list-group list-group-flush">
                    @forelse ($laneUtilization as $lane)
                        <li class="list-group-item d-flex justify-content-between bg-transparent">
                            <span>{{ $lane->name }}</span>
                            <span class="badge bg-primary rounded-pill fv-mono">{{ $lane->completed_count }}</span>
                        </li>
                    @empty
                        <li class="list-group-item text-muted bg-transparent">No lanes yet.</li>
                    @endforelse
                </ul>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header">Billiard Table Utilization (sessions)</div>
                <ul class="list-group list-group-flush">
                    @forelse ($tableUtilization as $table)
                        <li class="list-group-item d-flex justify-content-between bg-transparent">
                            <span>{{ $table->name }}</span>
                            <span class="badge bg-primary rounded-pill fv-mono">{{ $table->completed_count }}</span>
                        </li>
                    @empty
                        <li class="list-group-item text-muted bg-transparent">No tables yet.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header">{{ $isAdmin ? 'Recent Transactions' : 'Your Recent Transactions' }}</div>
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Receipt</th><th>Customer/Asset</th>
                        @if ($isAdmin)<th>Cashier</th>@endif
                        <th>Amount</th><th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recentTransactions as $t)
                        <tr>
                            <td class="fv-ref">{{ $t->receipt_no }}</td>
                            <td>{{ $t->booking?->assetLabel() }}</td>
                            @if ($isAdmin)<td>{{ $t->cashier->name }}</td>@endif
                            <td class="fv-mono">&#8369;{{ number_format($t->amount, 2) }}</td>
                            <td>{{ $t->created_at->format('M d, g:i A') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ $isAdmin ? 5 : 4 }}" class="text-center text-muted py-4">No transactions yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @include('partials.availability-modal', ['mode' => 'staff'])
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
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

    Chart.defaults.color = '#9aa3ad';
    Chart.defaults.borderColor = '#272c34';

    new Chart(document.getElementById('revenueChartLane'), {
        type: 'line',
        data: {
            labels: @json($revenueByDayLane->pluck('label')),
            datasets: [{
                label: 'Bowling Revenue (₱)',
                data: @json($revenueByDayLane->pluck('total')),
                borderColor: '#18d6c4',
                backgroundColor: 'rgba(24,214,196,.15)',
                tension: .3,
                fill: true,
            }]
        },
        options: { plugins: { legend: { display: false } } }
    });

    new Chart(document.getElementById('revenueChartTable'), {
        type: 'line',
        data: {
            labels: @json($revenueByDayTable->pluck('label')),
            datasets: [{
                label: 'Billiards Revenue (₱)',
                data: @json($revenueByDayTable->pluck('total')),
                borderColor: '#f5a524',
                backgroundColor: 'rgba(245,165,36,.15)',
                tension: .3,
                fill: true,
            }]
        },
        options: { plugins: { legend: { display: false } } }
    });

    new Chart(document.getElementById('peakChartLane'), {
        type: 'bar',
        data: {
            labels: @json($peakHoursLane->map(fn($h) => $h['hour'].':00')),
            datasets: [{
                label: 'Bowling Sessions',
                data: @json($peakHoursLane->pluck('total')),
                backgroundColor: '#18d6c4',
            }]
        },
        options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
    });

    new Chart(document.getElementById('peakChartTable'), {
        type: 'bar',
        data: {
            labels: @json($peakHoursTable->map(fn($h) => $h['hour'].':00')),
            datasets: [{
                label: 'Billiards Sessions',
                data: @json($peakHoursTable->pluck('total')),
                backgroundColor: '#f5a524',
            }]
        },
        options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
    });
</script>
@endpush
