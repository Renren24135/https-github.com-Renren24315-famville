@extends('layouts.app')

@section('title', 'Payment History')

@section('content')
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <div class="fv-eyebrow mb-1">Finance</div>
            <h3 class="mb-0"><i class="bi bi-clock-history"></i> {{ $isAdmin ? 'Payment History' : 'My Payment History' }}</h3>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <div class="btn-group" role="group">
                <a href="{{ route('staff.transactions.index') }}" class="btn btn-sm {{ ! $viewingMonth ? 'btn-primary' : 'btn-outline-secondary' }}">Today</a>
                <a href="{{ route('staff.transactions.index', ['all_time' => 1]) }}" class="btn btn-sm {{ $viewingMonth ? 'btn-primary' : 'btn-outline-secondary' }}">This Month</a>
            </div>
            @role('admin')
                <a href="{{ route('staff.monthly-archive.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-archive"></i> Monthly Archive</a>
            @endrole
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <x-fv-stat-tile :label="'Transactions '.($viewingMonth ? '(This Month)' : '(Today)')" :value="number_format($totalCount)" />
        </div>
        <div class="col-6 col-md-3">
            <x-fv-stat-tile :label="($isAdmin ? 'Total Collected' : 'Your Sales').' '.($viewingMonth ? '(This Month)' : '(Today)')" :value="'₱'.number_format($totalAmount, 2)" accent />
        </div>
    </div>
    <p class="text-muted small mb-1"><i class="bi bi-info-circle"></i> "This Month" resets to zero at the start of every new month &mdash; each closed month's totals are preserved permanently in the Monthly Archive, or viewable anytime with a custom date range below.</p>
    <p class="text-muted small mb-4"><i class="bi bi-info-circle"></i> A booking paid in two parts (reservation fee + session balance) counts and shows as one transaction, under its final receipt number &mdash; open "View" for the full payment breakdown.</p>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('staff.transactions.index') }}" class="row g-2 align-items-end">
                @if ($viewingMonth)
                    <input type="hidden" name="all_time" value="1">
                @endif
                <div class="col-12 col-sm-6 col-lg-4">
                    <label class="form-label small">Search</label>
                    <input type="text" name="search" class="form-control" placeholder="Customer name or receipt no." value="{{ $filters['search'] ?? '' }}">
                </div>
                <div class="col-6 col-sm-3 col-lg-2">
                    <label class="form-label small">From</label>
                    <input type="date" name="from" class="form-control" value="{{ $filters['from'] ?? '' }}">
                </div>
                <div class="col-6 col-sm-3 col-lg-2">
                    <label class="form-label small">To</label>
                    <input type="date" name="to" class="form-control" value="{{ $filters['to'] ?? '' }}">
                </div>
                <div class="col-6 col-sm-3 col-lg-2">
                    <label class="form-label small">Method</label>
                    <select name="payment_method" class="form-select">
                        <option value="">All</option>
                        <option value="cash" @selected(($filters['payment_method'] ?? '') === 'cash')>Cash</option>
                        <option value="gcash" @selected(($filters['payment_method'] ?? '') === 'gcash')>GCash</option>
                    </select>
                </div>
                <div class="col-6 col-sm-3 col-lg-2">
                    <label class="form-label small">Type</label>
                    <select name="type" class="form-select">
                        <option value="all" @selected($type === 'all')>All Assets</option>
                        <option value="lane" @selected($type === 'lane')>Bowling Only</option>
                        <option value="table" @selected($type === 'table')>Billiard Only</option>
                    </select>
                </div>
                <div class="col-6 col-sm-3 col-lg-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-fill"><i class="bi bi-search"></i> Filter</button>
                    @if ($hasActiveFilters)
                        <a href="{{ route('staff.transactions.index') }}" class="btn btn-outline-secondary" title="Reset to today"><i class="bi bi-x-lg"></i></a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm table-responsive-card">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Receipt No.</th>
                        <th>Customer</th>
                        <th>Asset</th>
                        @if ($isAdmin)<th>Cashier</th>@endif
                        <th>Method</th>
                        <th class="text-end">Amount</th>
                        <th>Date</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($transactions as $transaction)
                        @php
                            // If this is the session/balance payment and an
                            // earlier transaction on the same booking already
                            // covered the reservation fee, surface that first
                            // payment's method/amount/date here too — it's
                            // easy to miss as a separate row further down.
                            $firstTxn = $transaction->booking?->transactions->first();
                            $feeTxn = ($firstTxn && $firstTxn->id !== $transaction->id) ? $firstTxn : null;
                        @endphp
                        <tr>
                            <td class="fv-ref">{{ $transaction->receipt_no }}</td>
                            <td>{{ $transaction->booking->customer_name ?? '—' }}</td>
                            <td>{{ $transaction->booking?->assetLabel() }}</td>
                            @if ($isAdmin)<td>{{ $transaction->cashier->name }}</td>@endif
                            <td>
                                @if ($feeTxn)
                                    <div class="text-muted small mb-1">
                                        <i class="bi bi-{{ $feeTxn->payment_method === 'gcash' ? 'phone' : 'cash' }}"></i>
                                        {{ $feeTxn->payment_method === 'gcash' ? 'GCash' : 'Cash' }} (fee)
                                    </div>
                                @endif
                                <i class="bi bi-{{ $transaction->payment_method === 'gcash' ? 'phone' : 'cash' }}"></i>
                                {{ $transaction->payment_method === 'gcash' ? 'GCash' : 'Cash' }}
                            </td>
                            <td class="text-end fw-semibold fv-mono">
                                @if ($feeTxn)
                                    <div class="text-muted small fw-normal mb-1">&#8369;{{ number_format($feeTxn->amount, 2) }}</div>
                                @endif
                                &#8369;{{ number_format($transaction->amount, 2) }}
                            </td>
                            <td>
                                @if ($feeTxn)
                                    <div class="text-muted small mb-1">{{ $feeTxn->created_at->format('M d, Y g:i A') }}</div>
                                @endif
                                {{ $transaction->created_at->format('M d, Y g:i A') }}
                            </td>
                            <td class="text-end">
                                <a href="{{ route('staff.pos.receipt', $transaction) }}" class="btn btn-sm btn-outline-secondary">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ $isAdmin ? 8 : 7 }}" class="text-center text-muted py-4">No transactions match your filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $transactions->links() }}</div>
@endsection
