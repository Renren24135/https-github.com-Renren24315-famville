@extends('layouts.app')

@section('title', 'Billing History')

@section('content')
    <h3 class="mb-4"><i class="bi bi-receipt"></i> Billing History</h3>

    <div class="card shadow-sm table-responsive-card">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Receipt No.</th>
                        <th>Asset</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($transactions as $transaction)
                        <tr>
                            <td>{{ $transaction->receipt_no }}</td>
                            <td>{{ $transaction->booking?->assetLabel() }}</td>
                            <td>&#8369;{{ number_format($transaction->amount, 2) }}</td>
                            <td class="text-capitalize">{{ $transaction->payment_method }}</td>
                            <td>{{ $transaction->created_at->format('M d, Y g:i A') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">No transactions yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $transactions->links() }}</div>
@endsection
