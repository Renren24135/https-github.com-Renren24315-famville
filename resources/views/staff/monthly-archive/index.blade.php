@extends('layouts.app')

@section('title', 'Monthly Archive')

@section('content')
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <div class="fv-eyebrow mb-1">History</div>
            <h3 class="mb-0"><i class="bi bi-archive"></i> Monthly Archive</h3>
        </div>
        <a href="{{ route('staff.transactions.index') }}" class="btn btn-sm btn-outline-secondary">Back to Payment History</a>
    </div>
    <p class="text-muted small mb-4"><i class="bi bi-info-circle"></i> A permanent snapshot of each closed month's totals, saved automatically on the 1st of the following month &mdash; Payment History's "This Month" figures reset to zero right after.</p>

    @forelse ($archives as $archive)
        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                    <div>
                        <h5 class="mb-0">{{ $archive->month->format('F Y') }}</h5>
                        <div class="text-muted small"><i class="bi bi-clock-history"></i> Archived {{ $archive->archived_at?->format('M d, Y g:i A') }}</div>
                    </div>
                    <div class="fv-eyebrow">{{ number_format($archive->total_transactions) }} transactions</div>
                </div>
                <x-fv-lane-divider />
                <div class="row g-3 mt-3">
                    <div class="col-6 col-md-4">
                        <x-fv-stat-tile label="Bowling Revenue" :value="'₱'.number_format($archive->lane_revenue, 2)" />
                    </div>
                    <div class="col-6 col-md-4">
                        <x-fv-stat-tile label="Billiards Revenue" :value="'₱'.number_format($archive->table_revenue, 2)" />
                    </div>
                    <div class="col-6 col-md-4">
                        <x-fv-stat-tile label="Total Collected" :value="'₱'.number_format($archive->total_revenue, 2)" accent />
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="card shadow-sm">
            <div class="card-body text-center text-muted py-5">No months archived yet &mdash; the first one lands automatically at the start of next month.</div>
        </div>
    @endforelse

    <div class="mt-3">{{ $archives->links() }}</div>
@endsection
