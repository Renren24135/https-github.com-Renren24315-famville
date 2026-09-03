@extends('layouts.app')

@section('title', 'My Bookings')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0"><i class="bi bi-calendar-check"></i> My Bookings</h3>
        <a href="{{ route('customer.bookings.create') }}" class="btn btn-primary"><i class="bi bi-plus-circle"></i> New Booking</a>
    </div>

    <div class="card shadow-sm table-responsive-card">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Asset</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Qty</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Payment</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($bookings as $booking)
                        <tr>
                            <td>{{ $booking->assetLabel() }}</td>
                            <td>{{ $booking->booking_date->format('M d, Y') }}</td>
                            <td>{{ \Illuminate\Support\Carbon::parse($booking->start_time)->format('g:i A') }}</td>
                            <td>{{ $booking->scoresheets ? $booking->scoresheets.' sheet(s)' : $booking->hours.' hr(s)' }}</td>
                            <td>&#8369;{{ number_format($booking->total_amount, 2) }}</td>
                            <td><span class="badge bg-{{ $booking->status->color() }}">{{ $booking->status->label() }}</span></td>
                            <td><span class="badge bg-{{ $booking->payment_status->color() }}">{{ $booking->payment_status->label() }}</span></td>
                            <td>
                                <a href="{{ route('customer.bookings.show', $booking) }}" class="btn btn-sm btn-outline-secondary">View</a>
                                @if (in_array($booking->status->value, ['pending', 'approved']))
                                    <form method="POST" action="{{ route('customer.bookings.cancel', $booking) }}" class="d-inline" data-confirm="Cancel this booking?">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-danger">Cancel</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-4">No bookings yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $bookings->links() }}</div>
@endsection
