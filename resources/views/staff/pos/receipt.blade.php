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

                <dt class="col-6">Session Date</dt>
                <dd class="col-6 text-end">{{ $transaction->booking->booking_date->format('M d, Y') }} {{ \Illuminate\Support\Carbon::parse($transaction->booking->start_time)->format('g:i A') }}</dd>

                <dt class="col-6">Customer</dt>
                <dd class="col-6 text-end">{{ $transaction->booking->customer_name }}</dd>

                <dt class="col-6">Item</dt>
                <dd class="col-6 text-end">{{ $transaction->booking->assetLabel() }}</dd>

                <dt class="col-6">Quantity</dt>
                <dd class="col-6 text-end">{{ $transaction->booking->scoresheets ? $transaction->booking->scoresheets.' scoresheet(s)' : $transaction->booking->hours.' hour(s)' }}</dd>
            </dl>
            <hr>

            {{-- One line per payment actually collected for this booking —
                 a fee + balance split shows both, each with its own
                 receipt no., method, cashier, and amount. --}}
            @foreach ($transactions as $t)
                @php($isFeeLine = (float) $t->amount === (float) $transaction->booking->reservation_fee && $transaction->booking->reservation_fee > 0)
                <div class="d-flex justify-content-between mb-1">
                    <span>{{ $isFeeLine ? 'Reservation Fee (10%)' : 'Session Balance' }}</span>
                    <span class="fw-semibold">&#8369;{{ number_format($t->amount, 2) }}</span>
                </div>
                <div class="text-muted small mb-2">
                    {{ $t->payment_method === 'gcash' ? 'GCash' : 'Cash' }}
                    @if ($t->payment_method === 'gcash' && $t->gcash_reference)
                        (Ref: {{ $t->gcash_reference }})
                    @endif
                    &middot; {{ $t->created_at->format('M d, Y g:i A') }}
                    &middot; {{ $t->cashier->name }}
                    @if ($t->cash_tendered !== null)
                        <br>Cash Tendered &#8369;{{ number_format($t->cash_tendered, 2) }} &middot; Change &#8369;{{ number_format($t->change_due, 2) }}
                    @endif
                </div>
            @endforeach

            <hr>
            <div class="d-flex justify-content-between fs-4 fw-bold">
                <span>Total</span>
                <span>&#8369;{{ number_format($transactions->sum('amount'), 2) }}</span>
            </div>
        </div>
        <div class="card-footer d-flex justify-content-between">
            <a href="{{ route('staff.pos.index') }}" class="btn btn-outline-secondary btn-sm">Back to POS</a>
            <button type="button" id="printReceiptBtn" class="btn btn-primary btn-sm"><i class="bi bi-printer"></i> Print</button>
        </div>
    </div>

    <script nonce="{{ $cspNonce }}">
        document.getElementById('printReceiptBtn').addEventListener('click', function () {
            window.print();
        });
    </script>
@endsection
