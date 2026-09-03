@extends('layouts.app')

@section('title', 'Receipt')

@section('content')
    <h3 class="mb-4"><i class="bi bi-receipt"></i> Payment Receipt</h3>

    <div class="card shadow-sm" style="max-width: 500px;">
        <div class="card-body">
            <div class="text-center mb-3">
                <h5 class="mb-0">Famville Koronadal</h5>
                <p class="text-muted small mb-0">Bowling and Billiards</p>
            </div>
            <hr>
            <dl class="row mb-0">
                <dt class="col-6">Receipt No.</dt>
                <dd class="col-6 text-end">{{ $transaction->receipt_no }}</dd>

                <dt class="col-6">Date</dt>
                <dd class="col-6 text-end">{{ $transaction->created_at->format('M d, Y g:i A') }}</dd>

                <dt class="col-6">Customer</dt>
                <dd class="col-6 text-end">{{ $transaction->booking->customer_name }}</dd>

                <dt class="col-6">Item</dt>
                <dd class="col-6 text-end">{{ $transaction->booking->assetLabel() }}</dd>

                <dt class="col-6">Quantity</dt>
                <dd class="col-6 text-end">{{ $transaction->booking->scoresheets ? $transaction->booking->scoresheets.' scoresheet(s)' : $transaction->booking->hours.' hour(s)' }}</dd>

                <dt class="col-6">Payment Method</dt>
                <dd class="col-6 text-end text-capitalize">{{ $transaction->payment_method }}</dd>

                <dt class="col-6">Cashier</dt>
                <dd class="col-6 text-end">{{ $transaction->cashier->name }}</dd>
            </dl>
            <hr>
            <div class="d-flex justify-content-between fs-4 fw-bold">
                <span>Total</span>
                <span>&#8369;{{ number_format($transaction->amount, 2) }}</span>
            </div>
            @if ($transaction->cash_tendered !== null)
                <div class="d-flex justify-content-between text-muted small mt-2">
                    <span>Cash Tendered</span>
                    <span>&#8369;{{ number_format($transaction->cash_tendered, 2) }}</span>
                </div>
                <div class="d-flex justify-content-between text-muted small">
                    <span>Change</span>
                    <span>&#8369;{{ number_format($transaction->change_due, 2) }}</span>
                </div>
            @endif
        </div>
        <div class="card-footer d-flex justify-content-between">
            <a href="{{ route('staff.pos.index') }}" class="btn btn-outline-secondary btn-sm">Back to POS</a>
            <button onclick="window.print()" class="btn btn-primary btn-sm"><i class="bi bi-printer"></i> Print</button>
        </div>
    </div>
@endsection
