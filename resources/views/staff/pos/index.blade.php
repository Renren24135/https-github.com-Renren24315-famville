@extends('layouts.app')

@section('title', 'Point of Sale')

@section('content')
    <div data-auto-refresh="30"></div>

    <h3 class="mb-4"><i class="bi bi-cash-coin"></i> Point of Sale</h3>
    <p class="text-muted small">Sessions that are occupied or completed but still unpaid. Compute the bill and collect payment at the counter.</p>

    <div class="row g-3">
        @forelse ($pending as $booking)
            @php
                $isLane = $booking->scoresheets !== null;
                $unitSingular = $isLane ? 'scoresheet' : 'hour';
                $qty = $isLane ? $booking->scoresheets : $booking->hours;
                $unitLabel = $unitSingular.($qty === 1 ? '' : 's');
                $unitRate = $qty > 0 ? round($booking->total_amount / $qty, 2) : $booking->total_amount;
            @endphp
            <div class="col-12 col-sm-6 col-lg-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <h5 class="card-title mb-1">{{ $booking->assetLabel() }}</h5>
                            @if ($booking->session_ends_at)
                                <span class="badge asset-timer" data-countdown="{{ $booking->session_ends_at->toIso8601String() }}" data-session-start="{{ $booking->session_started_at?->toIso8601String() }}">--:--</span>
                            @endif
                        </div>
                        <p class="mb-1 text-muted small">{{ $booking->customer_name }} @if($booking->customer_phone) &middot; {{ $booking->customer_phone }} @endif</p>
                        <p class="mb-2 small">&#8369;{{ number_format($unitRate, 2) }} &times; {{ $qty }} {{ $unitLabel }} = <strong>&#8369;{{ number_format($booking->total_amount, 2) }}</strong></p>

                        <button type="button" class="btn btn-success w-100" data-bs-toggle="modal" data-bs-target="#payModal{{ $booking->id }}">
                            <i class="bi bi-calculator"></i> Compute &amp; Collect Payment
                        </button>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="payModal{{ $booking->id }}" tabindex="-1">
                <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered">
                    <div class="modal-content">
                        <form method="POST" action="{{ route('staff.pos.pay', $booking) }}">
                            @csrf
                            <input type="hidden" name="payment_method" value="cash">
                            <div class="modal-header">
                                <h5 class="modal-title"><i class="bi bi-calculator"></i> Cashier — {{ $booking->assetLabel() }}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <table class="table table-sm mb-3">
                                    <tbody>
                                        <tr>
                                            <td>Rate</td>
                                            <td class="text-end">&#8369;{{ number_format($unitRate, 2) }} / {{ $unitSingular }}</td>
                                        </tr>
                                        <tr>
                                            <td>Quantity</td>
                                            <td class="text-end">&times; {{ $qty }}</td>
                                        </tr>
                                        <tr class="table-light fw-bold">
                                            <td>Amount Due</td>
                                            <td class="text-end fs-5" id="amountDue{{ $booking->id }}">&#8369;{{ number_format($booking->total_amount, 2) }}</td>
                                        </tr>
                                    </tbody>
                                </table>

                                <div class="mb-3">
                                    <label class="form-label">Cash Tendered (₱)</label>
                                    <input type="number" step="0.01" min="{{ $booking->total_amount }}" name="cash_tendered"
                                           class="form-control form-control-lg" id="tendered{{ $booking->id }}"
                                           oninput="famvilleComputeChange({{ $booking->id }}, {{ $booking->total_amount }})" required autofocus>
                                </div>

                                <div class="d-flex justify-content-between fs-5">
                                    <span>Change</span>
                                    <strong id="change{{ $booking->id }}">&#8369;0.00</strong>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-success"><i class="bi bi-check-circle"></i> Confirm Payment</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="alert alert-secondary">No pending payments right now.</div>
            </div>
        @endforelse
    </div>
@endsection

@push('scripts')
<script>
    function famvilleComputeChange(id, due) {
        var tendered = parseFloat(document.getElementById('tendered' + id).value) || 0;
        var change = Math.max(0, tendered - due);
        document.getElementById('change' + id).textContent = '₱' + change.toFixed(2);
    }
</script>
@endpush
