@extends('layouts.app')

@section('title', 'Bookings')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0"><i class="bi bi-calendar-check"></i> Bookings</h3>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#walkInModal"><i class="bi bi-person-walking"></i> New Walk-In</button>
    </div>

    <ul class="nav nav-pills mb-3">
        @foreach (['pending' => 'Pending', 'approved' => 'Approved', 'occupied' => 'Occupied', 'completed' => 'Completed', 'cancelled' => 'Cancelled', 'rejected' => 'Rejected', 'all' => 'All'] as $value => $label)
            <li class="nav-item">
                <a class="nav-link {{ $status === $value ? 'active' : '' }}" href="{{ route('staff.bookings.index', ['status' => $value]) }}">{{ $label }}</a>
            </li>
        @endforeach
    </ul>

    <div class="card shadow-sm table-responsive-card">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Customer</th>
                        <th>Asset</th>
                        <th>Source</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($bookings as $booking)
                        <tr>
                            <td>{{ $booking->customer_name }}</td>
                            <td>{{ $booking->assetLabel() }}</td>
                            <td><span class="badge bg-{{ $booking->source->value === 'online' ? 'info' : 'dark' }}">{{ $booking->source->label() }}</span></td>
                            <td>{{ $booking->booking_date->format('M d, Y') }}</td>
                            <td>{{ \Illuminate\Support\Carbon::parse($booking->start_time)->format('g:i A') }}</td>
                            <td>&#8369;{{ number_format($booking->total_amount, 2) }}</td>
                            <td><span class="badge bg-{{ $booking->status->color() }}">{{ $booking->status->label() }}</span></td>
                            <td class="text-end">
                                @if ($booking->status->value === 'pending')
                                    <form method="POST" action="{{ route('staff.bookings.status', $booking) }}" class="d-inline">
                                        @csrf @method('PATCH')
                                        <input type="hidden" name="status" value="approved">
                                        <button class="btn btn-sm btn-success">Approve</button>
                                    </form>
                                    <form method="POST" action="{{ route('staff.bookings.status', $booking) }}" class="d-inline" data-confirm="Reject this booking?">
                                        @csrf @method('PATCH')
                                        <input type="hidden" name="status" value="rejected">
                                        <button class="btn btn-sm btn-outline-danger">Reject</button>
                                    </form>
                                @elseif ($booking->status->value === 'approved')
                                    <form method="POST" action="{{ route('staff.bookings.status', $booking) }}" class="d-inline">
                                        @csrf @method('PATCH')
                                        <input type="hidden" name="status" value="occupied">
                                        <button class="btn btn-sm btn-primary">Start Session</button>
                                    </form>
                                    <form method="POST" action="{{ route('staff.bookings.status', $booking) }}" class="d-inline" data-confirm="Cancel this booking?">
                                        @csrf @method('PATCH')
                                        <input type="hidden" name="status" value="cancelled">
                                        <button class="btn btn-sm btn-outline-danger">Cancel</button>
                                    </form>
                                @elseif ($booking->status->value === 'occupied')
                                    <form method="POST" action="{{ route('staff.bookings.status', $booking) }}" class="d-inline" data-confirm="Mark session as completed?">
                                        @csrf @method('PATCH')
                                        <input type="hidden" name="status" value="completed">
                                        <button class="btn btn-sm btn-success">Complete</button>
                                    </form>
                                    @if ($booking->payment_status->value === 'unpaid')
                                        <a href="{{ route('staff.pos.index') }}" class="btn btn-sm btn-outline-primary">Collect Payment</a>
                                    @endif
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-4">No bookings found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $bookings->links() }}</div>

    <div class="modal fade" id="walkInModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-scrollable">
            <div class="modal-content">
                <form method="POST" action="{{ route('staff.bookings.walkin') }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">New Walk-In Session</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Customer Name</label>
                            <input type="text" name="customer_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone (optional)</label>
                            <input type="text" name="customer_phone" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Asset Type</label>
                            <select name="asset_type" class="form-select" id="walkinAssetType" onchange="famvilleToggleWalkinFields()">
                                <option value="lane">Bowling Lane</option>
                                <option value="table">Billiard Table</option>
                            </select>
                        </div>
                        <div class="mb-3" id="walkinLaneField">
                            <label class="form-label">Lane</label>
                            <select name="asset_id" class="form-select">
                                @foreach (\App\Models\Lane::where('status', 'available')->orderBy('name')->get() as $lane)
                                    <option value="{{ $lane->id }}">{{ $lane->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3 d-none" id="walkinTableField">
                            <label class="form-label">Table</label>
                            <select name="asset_id" class="form-select" disabled>
                                @foreach (\App\Models\BilliardTable::where('status', 'available')->orderBy('name')->get() as $table)
                                    <option value="{{ $table->id }}">{{ $table->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3" id="walkinScoresheets">
                            <label class="form-label">Scoresheets</label>
                            <input type="number" name="scoresheets" min="1" max="20" value="1" class="form-control">
                        </div>
                        <div class="mb-3 d-none" id="walkinHours">
                            <label class="form-label">Hours</label>
                            <input type="number" name="hours" min="1" max="8" value="1" class="form-control" disabled>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Start Session</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    function famvilleToggleWalkinFields() {
        var isLane = document.getElementById('walkinAssetType').value === 'lane';

        document.getElementById('walkinLaneField').classList.toggle('d-none', !isLane);
        document.getElementById('walkinTableField').classList.toggle('d-none', isLane);
        document.getElementById('walkinScoresheets').classList.toggle('d-none', !isLane);
        document.getElementById('walkinHours').classList.toggle('d-none', isLane);

        document.querySelector('#walkinLaneField select').disabled = !isLane;
        document.querySelector('#walkinTableField select').disabled = isLane;
        document.querySelector('#walkinScoresheets input').disabled = !isLane;
        document.querySelector('#walkinHours input').disabled = isLane;
    }
</script>
@endpush
