@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <h3 class="mb-4"><i class="bi bi-speedometer2"></i> Executive Dashboard</h3>

    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card shadow-sm text-center h-100">
                <div class="card-body">
                    <i class="bi bi-cash-stack fs-3 text-success"></i>
                    <h4 class="mt-2 mb-0">&#8369;{{ number_format($todayRevenue, 2) }}</h4>
                    <div class="text-muted small">Today's Revenue</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card shadow-sm text-center h-100">
                <div class="card-body">
                    <i class="bi bi-hourglass-split fs-3 text-warning"></i>
                    <h4 class="mt-2 mb-0">{{ $pendingCount }}</h4>
                    <div class="text-muted small">Pending Approvals</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card shadow-sm text-center h-100">
                <div class="card-body">
                    <i class="bi bi-people-fill fs-3 text-danger"></i>
                    <h4 class="mt-2 mb-0">{{ $occupiedCount }} / {{ $totalAssets }}</h4>
                    <div class="text-muted small">Assets Occupied</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card shadow-sm text-center h-100">
                <div class="card-body">
                    <i class="bi bi-grid-3x3-gap fs-3 text-primary"></i>
                    <h4 class="mt-2 mb-0">{{ $totalAssets }}</h4>
                    <div class="text-muted small">Total Assets</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-7">
            <div class="card shadow-sm h-100">
                <div class="card-header">Revenue — Last 7 Days</div>
                <div class="card-body"><canvas id="revenueChart" height="120"></canvas></div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card shadow-sm h-100">
                <div class="card-header">Peak Hours (Sessions Started)</div>
                <div class="card-body"><canvas id="peakChart" height="120"></canvas></div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header">Lane Utilization (sessions)</div>
                <ul class="list-group list-group-flush">
                    @forelse ($laneUtilization as $lane)
                        <li class="list-group-item d-flex justify-content-between">
                            <span>{{ $lane->name }}</span>
                            <span class="badge bg-primary rounded-pill">{{ $lane->completed_count }}</span>
                        </li>
                    @empty
                        <li class="list-group-item text-muted">No lanes yet.</li>
                    @endforelse
                </ul>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header">Billiard Table Utilization (sessions)</div>
                <ul class="list-group list-group-flush">
                    @forelse ($tableUtilization as $table)
                        <li class="list-group-item d-flex justify-content-between">
                            <span>{{ $table->name }}</span>
                            <span class="badge bg-primary rounded-pill">{{ $table->completed_count }}</span>
                        </li>
                    @empty
                        <li class="list-group-item text-muted">No tables yet.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header">Recent Transactions</div>
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead class="table-light">
                    <tr><th>Receipt</th><th>Customer/Asset</th><th>Cashier</th><th>Amount</th><th>Date</th></tr>
                </thead>
                <tbody>
                    @forelse ($recentTransactions as $t)
                        <tr>
                            <td>{{ $t->receipt_no }}</td>
                            <td>{{ $t->booking?->assetLabel() }}</td>
                            <td>{{ $t->cashier->name }}</td>
                            <td>&#8369;{{ number_format($t->amount, 2) }}</td>
                            <td>{{ $t->created_at->format('M d, g:i A') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">No transactions yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
    new Chart(document.getElementById('revenueChart'), {
        type: 'line',
        data: {
            labels: @json($revenueByDay->pluck('label')),
            datasets: [{
                label: 'Revenue (₱)',
                data: @json($revenueByDay->pluck('total')),
                borderColor: '#0d6efd',
                backgroundColor: 'rgba(13,110,253,.15)',
                tension: .3,
                fill: true,
            }]
        },
        options: { plugins: { legend: { display: false } } }
    });

    new Chart(document.getElementById('peakChart'), {
        type: 'bar',
        data: {
            labels: @json($peakHours->map(fn($h) => $h['hour'].':00')),
            datasets: [{
                label: 'Sessions',
                data: @json($peakHours->pluck('total')),
                backgroundColor: '#198754',
            }]
        },
        options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
    });
</script>
@endpush
