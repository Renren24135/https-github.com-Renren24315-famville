@extends('layouts.app')

@section('title', 'Bookings')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <div class="fv-eyebrow mb-1">Operations</div>
            <h3 class="mb-0">
                @if ($isNewBookings)
                    <i class="bi bi-bell"></i> New Booking
                @else
                    <i class="bi bi-calendar-check"></i> Bookings
                @endif
            </h3>
        </div>
        @unless ($isAdmin)
            <a href="{{ route('staff.pos.index') }}" class="btn btn-primary"><i class="bi bi-person-walking"></i> New Walk-In (POS)</a>
        @endunless
    </div>

    @if ($isNewBookings)
        <div class="alert alert-info small d-flex justify-content-between align-items-center flex-wrap gap-2">
            <span><i class="bi bi-info-circle"></i> Online reservations for <strong>today or tomorrow</strong> that still need action.</span>
            <a href="{{ route('staff.bookings.index') }}" class="btn btn-sm btn-outline-secondary">View All Bookings</a>
        </div>
    @endif

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                @unless ($isNewBookings)
                    <ul class="nav nav-pills mb-0">
                        @foreach (['pending' => 'Pending', 'approved' => 'Approved', 'void_pending' => 'Void Pending', 'occupied' => 'Occupied', 'completed' => 'Completed', 'all' => 'All'] as $value => $label)
                            <li class="nav-item">
                                <a class="nav-link {{ $status === $value ? 'active' : '' }}" href="{{ route('staff.bookings.index', ['status' => $value, 'type' => $type]) }}">{{ $label }}</a>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <x-fv-status-dot color="warning" label="Pending" />
                @endif

                <div class="btn-group" role="group" aria-label="Filter by asset type">
                    @foreach (['all' => 'All Assets', 'lane' => 'Bowling Only', 'table' => 'Billiard Only'] as $value => $label)
                        <a href="{{ route('staff.bookings.index', ['status' => $status, 'type' => $value, 'when' => $isNewBookings ? 'soon' : null, 'q' => $search ?: null]) }}"
                           class="btn btn-sm {{ $type === $value ? 'btn-primary' : 'btn-outline-secondary' }}">{{ $label }}</a>
                    @endforeach
                </div>
            </div>

            @if ($status === 'approved')
                <form method="GET" action="{{ route('staff.bookings.index') }}" class="mt-3" style="max-width: 420px;">
                    <input type="hidden" name="status" value="approved">
                    <input type="hidden" name="type" value="{{ $type }}">
                    <label class="form-label small text-muted mb-1" for="bookingSearch">Search by customer name or ref no. &mdash; find who still needs to pay their session</label>
                    <div class="input-group">
                        <input type="search" name="q" id="bookingSearch" class="form-control" value="{{ $search }}" placeholder="e.g. Juan Dela Cruz or BK-000123">
                        <button type="submit" class="btn btn-outline-secondary"><i class="bi bi-search"></i></button>
                        @if ($search !== '')
                            <a href="{{ route('staff.bookings.index', ['status' => 'approved', 'type' => $type]) }}" class="btn btn-outline-secondary"><i class="bi bi-x-lg"></i></a>
                        @endif
                    </div>
                </form>
            @endif
        </div>
    </div>

    <div class="card shadow-sm table-responsive-card">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Ref</th>
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
                            <td class="text-nowrap fv-ref">{{ $booking->bookingNo() }}</td>
                            <td>
                                <button type="button" class="btn btn-link p-0 text-decoration-none" data-bs-toggle="modal" data-bs-target="#bookingDetail{{ $booking->id }}">
                                    {{ $booking->customer_name }} <i class="bi bi-info-circle text-muted small"></i>
                                </button>
                            </td>
                            <td>{{ $booking->assetLabel() }}</td>
                            <td><x-fv-status-dot :color="$booking->source->value === 'online' ? 'info' : 'secondary'" :label="$booking->source->label()" /></td>
                            <td>{{ $booking->booking_date->format('M d, Y') }}</td>
                            <td class="fv-mono">
                                {{ \Illuminate\Support\Carbon::parse($booking->start_time)->format('g:i A') }}
                                @if ($booking->scheduledEndTime())
                                    &ndash; {{ $booking->scheduledEndTime()->format('g:i A') }}
                                @endif
                            </td>
                            <td class="fv-mono">
                                @if ($booking->bookable instanceof \App\Models\BilliardTable && $booking->status->value === 'occupied' && $booking->hours === null)
                                    <span class="text-muted small">Billed at checkout</span>
                                @else
                                    &#8369;{{ number_format($booking->total_amount - $booking->reservation_fee, 2) }}
                                    @if ($booking->reservation_fee > 0)
                                        <div class="text-muted small">+ &#8369;{{ number_format($booking->reservation_fee, 2) }} fee paid via GCash</div>
                                    @endif
                                @endif
                            </td>
                            <td>
                                <x-fv-status-dot :color="$booking->status->color()" :label="$booking->status->label()" />
                                @if ($booking->status->value === 'void_pending' && $booking->cancel_reason)
                                    <div class="text-muted small mt-1">{{ $booking->cancel_reason }}</div>
                                @endif
                                @if ($booking->status->value === 'pending' && $booking->reservation_fee > 0)
                                    <div class="mt-1">
                                        <x-fv-status-dot :color="$booking->reservation_fee_status->color()" :label="'Fee ('.$booking->reservation_fee_method?->label().'): '.$booking->reservation_fee_status->label()" />
                                        @if ($booking->reservation_fee_method?->value === 'gcash' && $booking->reservation_fee_reference)
                                            <div class="text-muted small mt-1">Ref: {{ $booking->reservation_fee_reference }}</div>
                                        @endif
                                    </div>
                                @endif
                                @if ($booking->status->value === 'pending' && $booking->balance_payment_method?->value === 'gcash')
                                    <div class="mt-1">
                                        <x-fv-status-dot color="secondary" :label="'Session (GCash): '.($booking->balance_payment_reference ? 'Awaiting Verification' : 'Not Sent Yet')" />
                                        @if ($booking->balance_payment_reference)
                                            <div class="text-muted small mt-1">Ref: {{ $booking->balance_payment_reference }}</div>
                                        @endif
                                    </div>
                                @endif
                                @php($isOpenTimeTable = $booking->bookable instanceof \App\Models\BilliardTable && $booking->hours === null)
                                @if ($booking->status->value === 'approved' && ! $isOpenTimeTable)
                                    <div class="mt-1">
                                        <x-fv-status-dot :color="$booking->sessionPaymentColor()" :label="'Session Payment: '.$booking->sessionPaymentLabel()" />
                                        @if ($booking->payment_status->value === 'paid')
                                            <div class="text-muted small mt-1">via {{ $booking->balance_payment_method?->label() ?? 'Cash' }}</div>
                                        @elseif ($booking->balance_payment_reference)
                                            <div class="text-muted small mt-1">Ref: {{ $booking->balance_payment_reference }}</div>
                                        @endif
                                    </div>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="d-flex flex-wrap justify-content-end align-items-center gap-1">
                                @if ($isNewBookings)
                                    <a href="{{ route('staff.bookings.index', ['status' => 'pending']) }}" class="btn btn-sm btn-outline-secondary">Go to Bookings</a>
                                @elseif ($booking->status->value === 'pending')
                                    @php($feePendingGcash = $booking->reservation_fee > 0 && $booking->reservation_fee_status->value !== 'paid' && $booking->reservation_fee_method?->value === 'gcash')
                                    @php($balancePendingGcash = ! $isOpenTimeTable && $booking->payment_status->value === 'unpaid' && $booking->balance_payment_method?->value === 'gcash' && $booking->balance_payment_reference)
                                    @if ($booking->reservationFeeSettled())
                                        <form method="POST" action="{{ route('staff.bookings.status', $booking) }}" class="d-inline">
                                            @csrf @method('PATCH')
                                            <input type="hidden" name="status" value="approved">
                                            <button class="btn btn-sm btn-success">Approve</button>
                                        </form>
                                    @elseif ($feePendingGcash || $balancePendingGcash)
                                        {{-- One click verifies whichever GCash payments (fee and/or
                                        session) are still awaiting confirmation, recorded as a
                                        single transaction in payment history — and approves the
                                        booking in the same click, since the fee is the only thing
                                        that was still gating approval. --}}
                                        <form method="POST" action="{{ route('staff.bookings.status', $booking) }}" class="d-inline" data-confirm="Confirm the GCash payment{{ $feePendingGcash && $balancePendingGcash ? 's' : '' }} ({{ collect([$feePendingGcash ? $booking->reservation_fee_reference : null, $balancePendingGcash ? $booking->balance_payment_reference : null])->filter()->implode(' / ') }}) {{ $feePendingGcash && $balancePendingGcash ? 'were' : 'was' }} received? This will also approve the booking.">
                                            @csrf @method('PATCH')
                                            <input type="hidden" name="status" value="confirm_gcash_payment">
                                            <button class="btn btn-sm btn-outline-success">Verify GCash Payment &amp; Approve</button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('staff.bookings.status', $booking) }}" class="d-inline" data-confirm="Confirm the reservation fee was received in cash?">
                                            @csrf @method('PATCH')
                                            <input type="hidden" name="status" value="confirm_reservation_fee">
                                            <button class="btn btn-sm btn-outline-success">Confirm Fee Received</button>
                                        </form>
                                    @endif
                                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#voidReasonModal{{ $booking->id }}">Void</button>
                                @elseif ($booking->status->value === 'approved')
                                    @if (! $isOpenTimeTable && $booking->payment_status->value === 'unpaid')
                                        @if ($booking->balance_payment_method?->value === 'gcash' && $booking->balance_payment_reference)
                                            <form method="POST" action="{{ route('staff.bookings.status', $booking) }}" class="d-inline" data-confirm="Confirm this GCash reference ({{ $booking->balance_payment_reference }}) for the session price was received?">
                                                @csrf @method('PATCH')
                                                <input type="hidden" name="status" value="confirm_balance_payment">
                                                <button class="btn btn-sm btn-outline-success">Verify GCash Payment</button>
                                            </form>
                                        @else
                                            <a href="{{ route('staff.pos.index') }}" class="btn btn-sm btn-outline-primary">Collect Payment</a>
                                        @endif
                                    @else
                                        <form method="POST" action="{{ route('staff.bookings.status', $booking) }}" class="d-inline">
                                            @csrf @method('PATCH')
                                            <input type="hidden" name="status" value="occupied">
                                            <button class="btn btn-sm btn-primary">Start Session</button>
                                        </form>
                                    @endif
                                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#voidReasonModal{{ $booking->id }}">Void</button>
                                @elseif ($booking->status->value === 'void_pending')
                                    @if ($isAdmin)
                                        <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#approveVoidModal{{ $booking->id }}">Approve Void</button>
                                        <form method="POST" action="{{ route('staff.bookings.status', $booking) }}" class="d-inline" data-confirm="Deny the void and keep this reservation active?">
                                            @csrf @method('PATCH')
                                            <input type="hidden" name="status" value="void_denied">
                                            <button class="btn btn-sm btn-outline-secondary">Deny (Keep)</button>
                                        </form>
                                    @else
                                        <span class="text-muted small">Awaiting admin approval</span>
                                    @endif
                                @elseif ($booking->status->value === 'occupied')
                                    <form method="POST" action="{{ route('staff.bookings.status', $booking) }}" class="d-inline" data-confirm="Mark session as completed?">
                                        @csrf @method('PATCH')
                                        <input type="hidden" name="status" value="completed">
                                        <button class="btn btn-sm btn-success">Complete</button>
                                    </form>
                                    @if ($booking->payment_status->value === 'unpaid' && ! ($booking->bookable instanceof \App\Models\BilliardTable && $booking->hours === null))
                                        <a href="{{ route('staff.pos.index') }}" class="btn btn-sm btn-outline-primary">Collect Payment</a>
                                    @endif
                                @endif
                                </div>
                            </td>
                        </tr>

                        @if (in_array($booking->status->value, ['pending', 'approved'], true))
                            <div class="modal fade" id="voidReasonModal{{ $booking->id }}" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form method="POST" action="{{ route('staff.bookings.status', $booking) }}">
                                            @csrf @method('PATCH')
                                            <input type="hidden" name="status" value="void_pending">
                                            <div class="modal-header">
                                                <h5 class="modal-title"><i class="bi bi-exclamation-triangle text-danger"></i> Void — {{ $booking->customer_name }}</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <label for="cancel_reason{{ $booking->id }}" class="form-label">Why are you voiding this booking?</label>
                                                <textarea name="cancel_reason" id="cancel_reason{{ $booking->id }}" class="form-control" rows="3" maxlength="255" required
                                                    placeholder="e.g. Customer did not arrive within the 10-minute grace period."></textarea>
                                                <div class="form-text">This is sent to the admin for approval before anything is removed.</div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-danger">Flag for Void</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if ($booking->status->value === 'void_pending' && $isAdmin)
                            <div class="modal fade" id="approveVoidModal{{ $booking->id }}" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form method="POST" action="{{ route('staff.bookings.status', $booking) }}">
                                            @csrf @method('PATCH')
                                            <input type="hidden" name="status" value="void_approved">
                                            <div class="modal-header">
                                                <h5 class="modal-title"><i class="bi bi-exclamation-triangle text-danger"></i> Approve Void — {{ $booking->customer_name }}</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                @if ($booking->cancel_reason)
                                                    <p class="small text-muted mb-3"><strong>Cashier's reason:</strong> {{ $booking->cancel_reason }}</p>
                                                @endif
                                                <label for="void_admin_reason{{ $booking->id }}" class="form-label">Your reason for voiding this booking</label>
                                                <textarea name="void_admin_reason" id="void_admin_reason{{ $booking->id }}" class="form-control" rows="3" maxlength="500" required
                                                    placeholder="e.g. Confirmed with the cashier — customer never arrived."></textarea>
                                                <div class="form-text">The customer will be emailed this exact reason.</div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-danger">Approve Void &amp; Notify Customer</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endif

                            <div class="modal fade" id="bookingDetail{{ $booking->id }}" tabindex="-1">
                                <div class="modal-dialog modal-dialog-scrollable">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title"><i class="bi bi-receipt"></i> Booking Details &mdash; <span class="fv-ref">{{ $booking->bookingNo() }}</span></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <dl class="row mb-0">
                                                <dt class="col-sm-5">Customer</dt>
                                                <dd class="col-sm-7">{{ $booking->customer_name }} @if($booking->customer_phone)<div class="text-muted small">{{ $booking->customer_phone }}</div>@endif</dd>

                                                <dt class="col-sm-5">Asset</dt>
                                                <dd class="col-sm-7">{{ $booking->assetLabel() }}</dd>

                                                <dt class="col-sm-5">Date &amp; Time</dt>
                                                <dd class="col-sm-7">
                                                    {{ $booking->booking_date->format('M d, Y') }} &middot; {{ \Illuminate\Support\Carbon::parse($booking->start_time)->format('g:i A') }}
                                                    @if ($booking->scheduledEndTime())
                                                        &ndash; {{ $booking->scheduledEndTime()->format('g:i A') }}
                                                    @endif
                                                </dd>

                                                <dt class="col-sm-5">Quantity</dt>
                                                <dd class="col-sm-7">
                                                    @if ($booking->scoresheets !== null)
                                                        {{ $booking->scoresheets }} scoresheet(s)
                                                    @elseif ($booking->hours !== null)
                                                        {{ $booking->hours }} hour(s)
                                                    @else
                                                        <span class="text-muted">Open Time</span>
                                                    @endif
                                                </dd>

                                                <dt class="col-sm-5">Total Amount</dt>
                                                <dd class="col-sm-7 fv-mono">&#8369;{{ number_format($booking->total_amount, 2) }}</dd>

                                                @if ($booking->reservation_fee > 0)
                                                    <dt class="col-sm-5">Reservation Fee</dt>
                                                    <dd class="col-sm-7 fv-mono">&#8369;{{ number_format($booking->reservation_fee, 2) }}</dd>

                                                    <dt class="col-sm-5">Fee Payment Method</dt>
                                                    <dd class="col-sm-7">
                                                        <i class="bi bi-{{ $booking->reservation_fee_method?->value === 'gcash' ? 'phone' : 'shop' }}"></i>
                                                        {{ $booking->reservation_fee_method?->label() }}
                                                    </dd>

                                                    <dt class="col-sm-5">Fee Status</dt>
                                                    <dd class="col-sm-7">
                                                        <x-fv-status-dot :color="$booking->reservation_fee_status->color()" :label="$booking->reservation_fee_status->label()" />
                                                        @if ($booking->reservation_fee_paid_at)
                                                            <div class="text-muted small mt-1">Paid {{ $booking->reservation_fee_paid_at->format('M d, Y g:i A') }}</div>
                                                        @endif
                                                    </dd>

                                                    @if ($booking->reservation_fee_method?->value === 'gcash' && $booking->reservation_fee_reference)
                                                        <dt class="col-sm-5">GCash Reference</dt>
                                                        <dd class="col-sm-7 fv-ref">{{ $booking->reservation_fee_reference }}</dd>
                                                    @endif

                                                    <dt class="col-sm-5">Balance at Counter</dt>
                                                    <dd class="col-sm-7 fv-mono">&#8369;{{ number_format($booking->remainingBalance(), 2) }}</dd>
                                                @else
                                                    <dt class="col-sm-5">Reservation Fee</dt>
                                                    <dd class="col-sm-7"><span class="text-muted">None due</span></dd>
                                                @endif

                                                <dt class="col-sm-5">Balance Payment Preference</dt>
                                                <dd class="col-sm-7">
                                                    <i class="bi bi-{{ $booking->balance_payment_method?->value === 'gcash' ? 'phone' : 'shop' }}"></i>
                                                    {{ $booking->balance_payment_method?->label() ?? 'Not specified' }}
                                                </dd>

                                                @if ($booking->balance_payment_method?->value === 'gcash' && $booking->balance_payment_reference)
                                                    <dt class="col-sm-5">Balance GCash Reference</dt>
                                                    <dd class="col-sm-7 fv-ref">{{ $booking->balance_payment_reference }}</dd>
                                                @endif

                                                @if ($booking->status->value !== 'pending' && ! ($booking->bookable instanceof \App\Models\BilliardTable && $booking->hours === null))
                                                    <dt class="col-sm-5">Session Payment</dt>
                                                    <dd class="col-sm-7">
                                                        <x-fv-status-dot :color="$booking->sessionPaymentColor()" :label="$booking->sessionPaymentLabel()" />
                                                        @if ($booking->payment_status->value === 'paid')
                                                            <div class="text-muted small mt-1">via {{ $booking->balance_payment_method?->label() ?? 'Cash' }}</div>
                                                            @if ($booking->transactions->last())
                                                                <div class="text-muted small">Paid {{ $booking->transactions->last()->created_at->format('M d, Y g:i A') }}</div>
                                                            @endif
                                                        @endif
                                                    </dd>
                                                @endif

                                                @if ($booking->status->value !== 'pending')
                                                    <dt class="col-sm-5">Status</dt>
                                                    <dd class="col-sm-7"><x-fv-status-dot :color="$booking->status->color()" :label="$booking->status->label()" /></dd>
                                                @endif
                                            </dl>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                            @if ($booking->status->value === 'pending')
                                                @if ($isNewBookings)
                                                    <a href="{{ route('staff.bookings.index', ['status' => 'pending']) }}" class="btn btn-outline-secondary">Go to Bookings to act</a>
                                                @elseif ($booking->reservationFeeSettled())
                                                    <form method="POST" action="{{ route('staff.bookings.status', $booking) }}" class="d-inline">
                                                        @csrf @method('PATCH')
                                                        <input type="hidden" name="status" value="approved">
                                                        <button class="btn btn-success">Approve</button>
                                                    </form>
                                                @elseif ($booking->reservation_fee_method?->value === 'gcash')
                                                    <form method="POST" action="{{ route('staff.bookings.status', $booking) }}" class="d-inline" data-confirm="Confirm this GCash reference ({{ $booking->reservation_fee_reference }}) was received?">
                                                        @csrf @method('PATCH')
                                                        <input type="hidden" name="status" value="confirm_reservation_fee">
                                                        <button class="btn btn-outline-success">Verify GCash Payment</button>
                                                    </form>
                                                @else
                                                    <form method="POST" action="{{ route('staff.bookings.status', $booking) }}" class="d-inline" data-confirm="Confirm the reservation fee was received in cash?">
                                                        @csrf @method('PATCH')
                                                        <input type="hidden" name="status" value="confirm_reservation_fee">
                                                        <button class="btn btn-outline-success">Confirm Fee Received</button>
                                                    </form>
                                                @endif
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                    @empty
                        <tr><td colspan="9" class="text-center text-muted py-4">No bookings found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $bookings->links() }}</div>
@endsection
