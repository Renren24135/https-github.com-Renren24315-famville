@extends('layouts.app')

@section('title', 'Billing History')

@section('content')
    <h3 class="mb-4"><i class="bi bi-receipt"></i> Billing History</h3>

    <div class="row g-3 mb-4">
        <div class="col-6 col-md-4">
            <x-fv-stat-tile label="Total Spent" :value="'₱'.number_format($totalSpent, 2)" accent />
        </div>
        <div class="col-6 col-md-4">
            <x-fv-stat-tile label="Transactions" :value="$transactionCount" />
        </div>
        <div class="col-md-4">
            <x-fv-stat-tile
                label="Latest Payment"
                :value="$transactions->first() ? '₱'.number_format($transactions->first()->amount, 2) : '—'"
                :sub="$transactions->first()?->created_at?->format('M d, Y')" />
        </div>
    </div>

    @if ($transactions->isEmpty())
        <div class="card shadow-sm">
            <div class="card-body text-center py-5">
                <i class="bi bi-receipt fs-1 text-muted d-block mb-3"></i>
                <p class="mb-0 text-muted">No transactions yet.</p>
            </div>
        </div>
    @else
        <div class="row g-3">
            @foreach ($transactions as $transaction)
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3">
                            <div>
                                <div class="fv-ref mb-1">{{ $transaction->receipt_no }}</div>
                                <div class="fw-semibold">{{ $transaction->booking?->assetLabel() }}</div>
                                <div class="text-muted small">{{ $transaction->created_at->format('M d, Y g:i A') }}</div>
                            </div>
                            <div class="text-end">
                                <div class="fv-mono fs-5 fw-bold">&#8369;{{ number_format($transaction->amount, 2) }}</div>
                                <span class="badge bg-{{ $transaction->payment_method === 'gcash' ? 'info' : 'secondary' }}">
                                    <i class="bi bi-{{ $transaction->payment_method === 'gcash' ? 'phone' : 'cash' }}"></i>
                                    {{ $transaction->payment_method === 'gcash' ? 'GCash' : 'Cash' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-4">{{ $transactions->links() }}</div>
    @endif
@endsection
