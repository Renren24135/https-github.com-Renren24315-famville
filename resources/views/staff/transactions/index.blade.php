@extends('layouts.app')

@section('title', 'Payment History')

@section('content')
    <h3 class="mb-4"><i class="bi bi-clock-history"></i> Payment History</h3>

    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card shadow-sm text-center h-100">
                <div class="card-body">
                    <i class="bi bi-receipt fs-3 text-primary"></i>
                    <h4 class="mt-2 mb-0">{{ number_format($totalCount) }}</h4>
                    <div class="text-muted small">Transactions</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card shadow-sm text-center h-100">
                <div class="card-body">
                    <i class="bi bi-cash-stack fs-3 text-success"></i>
                    <h4 class="mt-2 mb-0">&#8369;{{ number_format($totalAmount, 2) }}</h4>
                    <div class="text-muted small">Total Collected</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('staff.transactions.index') }}" class="row g-2 align-items-end">
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
                    </select>
                </div>
                <div class="col-6 col-sm-3 col-lg-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-fill"><i class="bi bi-search"></i> Filter</button>
                    @if (array_filter($filters))
                        <a href="{{ route('staff.transactions.index') }}" class="btn btn-outline-secondary" title="Clear filters"><i class="bi bi-x-lg"></i></a>
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
                        <th>Cashier</th>
                        <th>Method</th>
                        <th class="text-end">Amount</th>
                        <th>Date</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($transactions as $transaction)
                        <tr>
                            <td>{{ $transaction->receipt_no }}</td>
                            <td>{{ $transaction->booking->customer_name ?? '—' }}</td>
                            <td>{{ $transaction->booking?->assetLabel() }}</td>
                            <td>{{ $transaction->cashier->name }}</td>
                            <td class="text-capitalize">{{ $transaction->payment_method }}</td>
                            <td class="text-end fw-semibold">&#8369;{{ number_format($transaction->amount, 2) }}</td>
                            <td>{{ $transaction->created_at->format('M d, Y g:i A') }}</td>
                            <td class="text-end">
                                <a href="{{ route('staff.pos.receipt', $transaction) }}" class="btn btn-sm btn-outline-secondary">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-4">No transactions match your filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $transactions->links() }}</div>
@endsection
