@extends('layouts.app')

@section('title', 'Point of Sale')

@section('content')
    <div data-auto-refresh="30"></div>

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <h3 class="mb-0"><i class="bi bi-cash-coin"></i> Point of Sale</h3>
        <div class="fv-floorplan-switcher" role="group" aria-label="POS view">
            <button type="button" class="fv-floorplan-switch-btn is-active" id="fvPosBtnPos"><i class="bi bi-cash-coin"></i> POS</button>
            <button type="button" class="fv-floorplan-switch-btn" id="fvPosBtnWalkin"><i class="bi bi-person-walking"></i> Walk-In</button>
        </div>
    </div>

    <div class="mb-4" style="max-width: 420px;">
        <label class="form-label small text-muted mb-1" for="posSearch"><i class="bi bi-search"></i> Search by customer name or reference no.</label>
        <input type="search" id="posSearch" class="form-control form-control-lg" placeholder="e.g. Juan Dela Cruz or BK-000123">
    </div>

    <div id="fvPosViewPos">
    <div class="row g-4">
        <div class="col-12">
            <div class="fv-eyebrow mb-1"><i class="bi bi-calculator"></i> Collect Payment</div>
            <p class="text-muted small">Online reservations (and any finished Open Time walk-in) that are still unpaid. Compute the bill and collect payment here &mdash; for a lane or Fixed Hours table, payment must be collected before the session can be started.</p>

            <div class="row g-3" id="posPendingList">
                @forelse ($pending as $booking)
                    @php
                        $isLane = $booking->bookable_type === \App\Models\Lane::class;
                        $unitSingular = $isLane ? 'scoresheet' : 'hour';
                        $qty = $isLane ? $booking->scoresheets : $booking->hours;
                        $unitLabel = $unitSingular.($qty === 1 ? '' : 's');
                        // The pure session cost, before any reservation fee — used
                        // for the rate/qty breakdown regardless of how/when the fee
                        // was (or wasn't) collected.
                        $sessionSubtotal = $booking->total_amount - $booking->reservation_fee;
                        $unitRate = $qty > 0 ? round($sessionSubtotal / $qty, 2) : $sessionSubtotal;
                        // What's actually left to collect right now at the counter.
                        $baseAmount = $booking->remainingBalance();
                        $feeAlreadyPaid = $booking->reservation_fee_paid_at !== null;
                    @endphp
                    <div class="col-12 col-md-6 fv-pos-item" data-search="{{ strtolower($booking->customer_name.' '.$booking->bookingNo()) }}">
                        <div class="card shadow-sm h-100 fv-booking-card fv-booking-card--warning">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <h5 class="card-title mb-1">{{ $booking->assetLabel() }}</h5>
                                    @if ($isLane && $booking->session_started_at && $booking->session_ends_at)
                                        <span class="badge bg-secondary fv-mono">{{ $booking->session_started_at->format('g:i A') }} &ndash; {{ $booking->session_ends_at->format('g:i A') }}</span>
                                    @elseif ($isLane && $booking->session_started_at)
                                        <span class="badge bg-secondary">Since {{ $booking->session_started_at->format('g:i A') }}</span>
                                    @elseif (!$isLane && $booking->session_ends_at)
                                        <span class="badge asset-timer" data-countdown="{{ $booking->session_ends_at->toIso8601String() }}" data-session-start="{{ $booking->session_started_at?->toIso8601String() }}">--:--</span>
                                    @endif
                                </div>
                                <p class="mb-1 text-muted small">{{ $booking->customer_name }} @if($booking->customer_phone) &middot; {{ $booking->customer_phone }} @endif</p>
                                @if ($booking->reservation_fee > 0)
                                    <p class="mb-1 small fv-mono">&#8369;{{ number_format($booking->total_amount, 2) }} &minus; &#8369;{{ number_format($booking->reservation_fee, 2) }} fee = &#8369;{{ number_format($baseAmount, 2) }}</p>
                                    @if ($feeAlreadyPaid)
                                        <p class="mb-1 small text-success"><i class="bi bi-check-circle"></i> Reservation fee already paid via {{ $booking->reservation_fee_method?->label() }}</p>
                                    @else
                                        <p class="mb-1 small text-muted">Reservation fee not yet paid</p>
                                    @endif
                                @endif
                                <p class="mb-1 small fv-mono">&#8369;{{ number_format($unitRate, 2) }} &times; {{ $qty }} {{ $unitLabel }} = &#8369;{{ number_format($sessionSubtotal, 2) }}</p>
                                <p class="mb-2 small">Amount due: <strong class="fv-mono">&#8369;{{ number_format($baseAmount, 2) }}</strong></p>

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
                                                @if ($booking->reservation_fee > 0 && ! $feeAlreadyPaid)
                                                    <tr>
                                                        <td>Reservation Fee</td>
                                                        <td class="text-end">&#8369;{{ number_format($booking->reservation_fee, 2) }}</td>
                                                    </tr>
                                                @elseif ($feeAlreadyPaid)
                                                    <tr class="text-success">
                                                        <td>Reservation Fee</td>
                                                        <td class="text-end">Already paid</td>
                                                    </tr>
                                                @endif
                                                <tr class="table-light fw-bold">
                                                    <td>Amount Due</td>
                                                    <td class="text-end fs-5" id="amountDue{{ $booking->id }}">&#8369;{{ number_format($baseAmount, 2) }}</td>
                                                </tr>
                                            </tbody>
                                        </table>

                                        <label class="form-label d-block">Payment Method</label>
                                        <div class="btn-group mb-3" role="group">
                                            <input type="radio" class="btn-check fv-pos-pay-method" name="payment_method" id="pay_cash{{ $booking->id }}" value="cash" checked
                                                   data-booking-id="{{ $booking->id }}" data-method="cash">
                                            <label class="btn btn-outline-secondary" for="pay_cash{{ $booking->id }}"><i class="bi bi-cash"></i> Cash</label>

                                            <input type="radio" class="btn-check fv-pos-pay-method" name="payment_method" id="pay_gcash{{ $booking->id }}" value="gcash"
                                                   data-booking-id="{{ $booking->id }}" data-method="gcash">
                                            <label class="btn btn-outline-secondary" for="pay_gcash{{ $booking->id }}"><i class="bi bi-phone"></i> GCash</label>
                                        </div>

                                        <div id="cashFields{{ $booking->id }}">
                                            <div class="mb-3">
                                                <label class="form-label">Cash Tendered (₱)</label>
                                                <input type="number" step="0.01" min="{{ $baseAmount }}" name="cash_tendered"
                                                       class="form-control form-control-lg fv-pos-tendered" id="tendered{{ $booking->id }}"
                                                       data-booking-id="{{ $booking->id }}" data-amount="{{ $baseAmount }}" required>
                                            </div>

                                            <div class="d-flex justify-content-between fs-5">
                                                <span>Change</span>
                                                <strong id="change{{ $booking->id }}">&#8369;0.00</strong>
                                            </div>
                                        </div>

                                        <div id="gcashFields{{ $booking->id }}" class="d-none">
                                            <label class="form-label">GCash Reference Number</label>
                                            <input type="text" name="gcash_reference" class="form-control" id="gcashRef{{ $booking->id }}" placeholder="e.g. 1234567890123">
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
        </div>
    </div>
    </div>

    <div id="fvPosViewWalkin" class="d-none">
    <div class="row g-4">
        <div class="col-lg-5 fv-pos-divider">
            <div class="fv-eyebrow mb-1"><i class="bi bi-person-walking"></i> Walk-In</div>
            <p class="text-muted small">Start a walk-in session and collect payment in one step &mdash; a lane or Fixed Hours table is paid right here; an Open Time table is billed once it finishes, over on the left.</p>

            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <form method="POST" action="{{ route('staff.pos.walkin') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Customer Name</label>
                            <input type="text" name="customer_name" class="form-control" value="{{ old('customer_name') }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone (optional)</label>
                            <input type="text" name="customer_phone" class="form-control" value="{{ old('customer_phone') }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Asset Type</label>
                            <select name="asset_type" class="form-select" id="walkinAssetType">
                                <option value="lane" {{ old('asset_type', 'lane') === 'lane' ? 'selected' : '' }}>Bowling Lane</option>
                                <option value="table" {{ old('asset_type') === 'table' ? 'selected' : '' }}>Billiard Table</option>
                            </select>
                        </div>
                        <div class="mb-3" id="walkinLaneSelect">
                            <label class="form-label">Choose a Lane</label>
                            <select name="lane_id" id="walkinLaneSelectInput" class="form-select">
                                <option value="">Select a lane&hellip;</option>
                                @foreach ($lanes as $lane)
                                    <option value="{{ $lane->id }}" {{ $lane->status->value !== 'available' ? 'disabled' : '' }} {{ (string) old('lane_id') === (string) $lane->id ? 'selected' : '' }}>
                                        {{ $lane->name }} &mdash; {{ $lane->status->label() }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3 d-none" id="walkinTableSelect">
                            <label class="form-label">Choose a Table</label>
                            <select name="table_id" id="walkinTableSelectInput" class="form-select" disabled>
                                <option value="">Select a table&hellip;</option>
                                @foreach ($tables as $table)
                                    <option value="{{ $table->id }}" {{ $table->status->value !== 'available' ? 'disabled' : '' }} {{ (string) old('table_id') === (string) $table->id ? 'selected' : '' }}>
                                        {{ $table->name }} &mdash; {{ $table->status->label() }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3 d-none" id="walkinAvailabilityPanel">
                            <label class="form-label d-block">Today's Timetable &mdash; <span id="walkinAvailName"></span></label>
                            <div id="walkinAvailLoading" class="text-muted small py-2"><div class="spinner-border spinner-border-sm"></div> Loading&hellip;</div>
                            <div id="walkinAvailError" class="alert alert-danger small py-2 mb-0 d-none"></div>
                            <table class="table table-sm align-middle mb-0 d-none" id="walkinAvailTable">
                                <tbody id="walkinAvailSlots"></tbody>
                            </table>
                            <div class="d-flex flex-wrap gap-3 mt-2">
                                <span class="fv-status fv-status-success"><span class="fv-status-indicator"></span>Free</span>
                                <span class="fv-status fv-status-warning"><span class="fv-status-indicator"></span>Reserved</span>
                                <span class="fv-status fv-status-danger"><span class="fv-status-indicator"></span>In Use</span>
                                <span class="fv-status fv-status-info"><span class="fv-status-indicator"></span>Pending Approval</span>
                        <span class="fv-status fv-status-secondary"><span class="fv-status-indicator"></span>Unavailable</span>
                        <span class="fv-status fv-status-secondary"><span class="fv-status-indicator"></span>Closed</span>
                            </div>
                        </div>
                        <div class="mb-3" id="walkinScoresheets">
                            <label class="form-label">Scoresheets</label>
                            <input type="number" name="scoresheets" min="1" max="20" value="{{ old('scoresheets', 1) }}" class="form-control">
                        </div>
                        <div class="mb-3 d-none" id="walkinDurationMode">
                            <label class="form-label d-block">How will they play?</label>
                            <div class="btn-group" role="group">
                                <input type="radio" class="btn-check" name="duration_mode" id="walkin_mode_fixed" value="fixed" {{ old('duration_mode', 'fixed') === 'fixed' ? 'checked' : '' }}>
                                <label class="btn btn-outline-secondary btn-sm" for="walkin_mode_fixed">Fixed Hours</label>

                                <input type="radio" class="btn-check" name="duration_mode" id="walkin_mode_open" value="open" {{ old('duration_mode') === 'open' ? 'checked' : '' }}>
                                <label class="btn btn-outline-secondary btn-sm" for="walkin_mode_open">Open Time</label>
                            </div>
                        </div>
                        <div class="mb-3 d-none" id="walkinHours">
                            <label class="form-label">Hours</label>
                            <input type="number" name="hours" min="1" max="8" value="{{ old('hours', 1) }}" class="form-control" disabled>
                        </div>
                        <div class="d-none" id="walkinOpenTimeNote">
                            <p class="text-muted small"><i class="bi bi-stopwatch"></i> No fixed duration &mdash; the bill is computed from actual time played when the session ends, and collected later on the left.</p>
                        </div>

                        <div id="walkinPaymentSection">
                            <hr>
                            <label class="form-label d-block">Collect Payment</label>
                            <div class="btn-group mb-3" role="group">
                                <input type="radio" class="btn-check" name="payment_method" id="walkin_pay_cash" value="cash" {{ old('payment_method', 'cash') === 'cash' ? 'checked' : '' }}>
                                <label class="btn btn-outline-secondary" for="walkin_pay_cash"><i class="bi bi-cash"></i> Cash</label>

                                <input type="radio" class="btn-check" name="payment_method" id="walkin_pay_gcash" value="gcash" {{ old('payment_method') === 'gcash' ? 'checked' : '' }}>
                                <label class="btn btn-outline-secondary" for="walkin_pay_gcash"><i class="bi bi-phone"></i> GCash</label>
                            </div>

                            <div id="walkinCashFields" class="mb-3">
                                <label class="form-label">Cash Tendered (₱)</label>
                                <input type="number" step="0.01" min="0" name="cash_tendered" id="walkinCashTendered" class="form-control" value="{{ old('cash_tendered') }}">
                            </div>
                            <div id="walkinGcashFields" class="mb-3 d-none">
                                <label class="form-label">GCash Reference Number</label>
                                <input type="text" name="gcash_reference" id="walkinGcashRef" class="form-control" value="{{ old('gcash_reference') }}" placeholder="e.g. 1234567890123">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100"><i class="bi bi-play-circle"></i> Start Session</button>
                    </form>
                </div>
            </div>

            <div class="fv-eyebrow mb-2"><i class="bi bi-broadcast"></i> Playing Now (Walk-Ins)</div>
            <div id="posPlayingNowList">
            @forelse ($activeWalkIns as $walkin)
                @php($walkinIsLane = $walkin->bookable_type === \App\Models\Lane::class)
                <div class="card shadow-sm mb-2 fv-pos-item" data-search="{{ strtolower($walkin->customer_name.' '.$walkin->bookingNo()) }}">
                    <div class="card-body py-2 px-3 d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-semibold">{{ $walkin->assetLabel() }}</div>
                            <div class="text-muted small">{{ $walkin->customer_name }}</div>
                        </div>
                        @if (! $walkinIsLane && $walkin->hours === null)
                            <span class="badge bg-secondary">Since {{ $walkin->session_started_at->format('g:i A') }}</span>
                        @elseif ($walkin->session_ends_at)
                            <span class="badge asset-timer" data-countdown="{{ $walkin->session_ends_at->toIso8601String() }}" data-session-start="{{ $walkin->session_started_at?->toIso8601String() }}">--:--</span>
                        @endif
                    </div>
                </div>
            @empty
                <p class="text-muted small">No walk-in sessions in progress.</p>
            @endforelse
            </div>
        </div>

        <div class="col-lg-7">
            <div class="fv-eyebrow mb-2"><i class="bi bi-door-closed"></i> Occupied Spaces</div>
            <div class="row g-2">
                @php($occupiedAssets = $lanes->where('status', \App\Enums\AssetStatus::Occupied)->concat($tables->where('status', \App\Enums\AssetStatus::Occupied)))
                @forelse ($occupiedAssets as $asset)
                    <div class="col-6 col-md-4">
                        <div class="card shadow-sm h-100">
                            <div class="card-body py-2 px-3">
                                <div class="fw-semibold">{{ $asset->name }}</div>
                                <x-fv-status-dot color="danger" label="Occupied" />
                                <div class="text-muted small mt-1">{{ $asset->activeBooking()?->customer_name ?? 'In use' }}</div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12"><p class="text-muted small mb-0">No spaces are occupied right now.</p></div>
                @endforelse
            </div>
        </div>
    </div>
    </div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce }}">
    document.addEventListener('DOMContentLoaded', function () {
        var posBtn = document.getElementById('fvPosBtnPos');
        var walkinBtn = document.getElementById('fvPosBtnWalkin');
        var posView = document.getElementById('fvPosViewPos');
        var walkinView = document.getElementById('fvPosViewWalkin');

        // Remembered in the URL so the 30-second auto-refresh and a
        // validation-error redirect both land back on the same view.
        function showView(view) {
            var isWalkin = view === 'walkin';
            posBtn.classList.toggle('is-active', !isWalkin);
            walkinBtn.classList.toggle('is-active', isWalkin);
            posView.classList.toggle('d-none', isWalkin);
            walkinView.classList.toggle('d-none', !isWalkin);

            var url = new URL(window.location.href);
            url.searchParams.set('view', view);
            window.history.replaceState(null, '', url.toString());
        }

        posBtn.addEventListener('click', function () { showView('pos'); });
        walkinBtn.addEventListener('click', function () { showView('walkin'); });

        showView({{ Js::from(request('view') === 'walkin' || old('asset_type') !== null ? 'walkin' : 'pos') }});
    });
</script>
<script nonce="{{ $cspNonce }}">
    function famvillePosSearch() {
        var query = document.getElementById('posSearch').value.trim().toLowerCase();

        document.querySelectorAll('.fv-pos-item').forEach(function (item) {
            var matches = !query || item.getAttribute('data-search').indexOf(query) !== -1;
            item.classList.toggle('d-none', !matches);
        });
    }

    function famvilleComputeChange(id, due) {
        var tendered = parseFloat(document.getElementById('tendered' + id).value) || 0;
        var change = Math.max(0, tendered - due);
        document.getElementById('change' + id).textContent = '₱' + change.toFixed(2);
    }

    function famvilleSetPosPayMethod(id, method) {
        var isCash = method === 'cash';

        document.getElementById('cashFields' + id).classList.toggle('d-none', !isCash);
        document.getElementById('gcashFields' + id).classList.toggle('d-none', isCash);

        document.getElementById('tendered' + id).required = isCash;
        document.getElementById('gcashRef' + id).required = !isCash;
    }

    function famvilleSetWalkinPayMethod(method) {
        var isCash = method === 'cash';

        document.getElementById('walkinCashFields').classList.toggle('d-none', !isCash);
        document.getElementById('walkinGcashFields').classList.toggle('d-none', isCash);
    }

    function famvilleToggleWalkinFields() {
        var isLane = document.getElementById('walkinAssetType').value === 'lane';
        var mode = (document.querySelector('input[name="duration_mode"]:checked') || {}).value || 'fixed';
        var isFixed = !isLane && mode === 'fixed';
        var isOpen = !isLane && mode === 'open';
        var needsPayment = isLane || isFixed;

        document.getElementById('walkinScoresheets').classList.toggle('d-none', !isLane);
        document.querySelector('#walkinScoresheets input').disabled = !isLane;

        document.getElementById('walkinLaneSelect').classList.toggle('d-none', !isLane);
        document.getElementById('walkinTableSelect').classList.toggle('d-none', isLane);
        document.querySelector('#walkinLaneSelect select').disabled = !isLane;
        document.querySelector('#walkinTableSelect select').disabled = isLane;

        document.getElementById('walkinDurationMode').classList.toggle('d-none', isLane);
        // A lane booking hides this group but its "Fixed Hours" radio stays
        // checked by default — disable the whole group so it isn't still
        // submitted as duration_mode=fixed, which would otherwise make the
        // (also-disabled, lane-irrelevant) hours field wrongly required.
        document.querySelectorAll('#walkinDurationMode input[name="duration_mode"]').forEach(function (el) { el.disabled = isLane; });
        document.getElementById('walkinHours').classList.toggle('d-none', !isFixed);
        document.querySelector('#walkinHours input').disabled = !isFixed;
        document.getElementById('walkinOpenTimeNote').classList.toggle('d-none', !isOpen);

        // Switching between Bowling/Billiards leaves the other type's select
        // holding a stale value — clear it so its old timetable doesn't keep
        // showing under the now-irrelevant selection.
        document.querySelector(isLane ? '#walkinTableSelect select' : '#walkinLaneSelect select').value = '';
        document.getElementById('walkinAvailabilityPanel').classList.add('d-none');

        // Open Time's price isn't known yet, so there's nothing to collect
        // upfront — that section only applies once the price is known.
        document.getElementById('walkinPaymentSection').classList.toggle('d-none', !needsPayment);
        // Hiding the section isn't enough — the "Cash" radio stays checked
        // by default and, left enabled, still submits payment_method=cash
        // with no (also-hidden) cash_tendered value, which the server then
        // wrongly demands. Disable every field in the section so nothing
        // stray gets submitted for an Open Time table.
        document.querySelectorAll('#walkinPaymentSection input').forEach(function (el) { el.disabled = !needsPayment; });
        var payCash = document.getElementById('walkin_pay_cash').checked;
        document.getElementById('walkinCashTendered').required = needsPayment && payCash;
        document.getElementById('walkinGcashRef').required = needsPayment && !payCash;
    }

    var famvilleWalkinAvailUrl = {{ Js::from(route('availability.show', ['type' => '__TYPE__', 'id' => '__ID__'])) }};

    function famvilleWalkinAvailability(type, selectEl) {
        var id = selectEl.value;
        var panel = document.getElementById('walkinAvailabilityPanel');

        if (!id) {
            panel.classList.add('d-none');
            return;
        }

        var loading = document.getElementById('walkinAvailLoading');
        var errorBox = document.getElementById('walkinAvailError');
        var table = document.getElementById('walkinAvailTable');
        var tbody = document.getElementById('walkinAvailSlots');

        document.getElementById('walkinAvailName').textContent = selectEl.options[selectEl.selectedIndex].text;
        panel.classList.remove('d-none');
        loading.classList.remove('d-none');
        errorBox.classList.add('d-none');
        table.classList.add('d-none');
        tbody.innerHTML = '';

        var url = famvilleWalkinAvailUrl.replace('__TYPE__', type).replace('__ID__', id)
            + '?date=' + encodeURIComponent('{{ now()->toDateString() }}');

        fetch(url, { headers: { 'Accept': 'application/json' } })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('Could not load the timetable.');
                }
                return response.json();
            })
            .then(function (data) {
                var statusMeta = {
                    free: { color: 'success', label: 'Free' },
                    reserved: { color: 'warning', label: 'Reserved' },
                    in_use: { color: 'danger', label: 'In Use' },
                    pending: { color: 'info', label: 'Pending Approval' },
                    past: { color: 'secondary', label: 'Unavailable' },
                    closed: { color: 'secondary', label: 'Closed' },
                };

                data.slots.forEach(function (slot) {
                    var meta = statusMeta[slot.status] || statusMeta.free;
                    var row = document.createElement('tr');
                    row.innerHTML = '<td class="fv-mono">' + slot.label + '</td>' +
                        '<td class="text-end"><span class="fv-status fv-status-' + meta.color + '"><span class="fv-status-indicator"></span>' + meta.label + '</span></td>';
                    tbody.appendChild(row);
                });
                table.classList.remove('d-none');
            })
            .catch(function (err) {
                errorBox.textContent = err.message;
                errorBox.classList.remove('d-none');
            })
            .finally(function () {
                loading.classList.add('d-none');
            });
    }

    document.addEventListener('DOMContentLoaded', function () {
        famvilleToggleWalkinFields();

        // Wired here instead of inline oninput/onchange attributes (CSP).
        var searchInput = document.getElementById('posSearch');
        if (searchInput) {
            searchInput.addEventListener('input', famvillePosSearch);
        }

        document.querySelectorAll('.fv-pos-pay-method').forEach(function (input) {
            input.addEventListener('change', function () {
                famvilleSetPosPayMethod(this.dataset.bookingId, this.dataset.method);
            });
        });

        document.querySelectorAll('.fv-pos-tendered').forEach(function (input) {
            input.addEventListener('input', function () {
                famvilleComputeChange(this.dataset.bookingId, parseFloat(this.dataset.amount));
            });
        });

        var walkinAssetType = document.getElementById('walkinAssetType');
        if (walkinAssetType) {
            walkinAssetType.addEventListener('change', famvilleToggleWalkinFields);
        }

        var walkinLaneSelect = document.getElementById('walkinLaneSelectInput');
        if (walkinLaneSelect) {
            walkinLaneSelect.addEventListener('change', function () {
                famvilleWalkinAvailability('lane', this);
            });
        }

        var walkinTableSelect = document.getElementById('walkinTableSelectInput');
        if (walkinTableSelect) {
            walkinTableSelect.addEventListener('change', function () {
                famvilleWalkinAvailability('table', this);
            });
        }

        ['walkin_mode_fixed', 'walkin_mode_open'].forEach(function (id) {
            var el = document.getElementById(id);
            if (el) {
                el.addEventListener('change', famvilleToggleWalkinFields);
            }
        });

        var walkinPayCash = document.getElementById('walkin_pay_cash');
        if (walkinPayCash) {
            walkinPayCash.addEventListener('change', function () {
                famvilleSetWalkinPayMethod('cash');
            });
        }
        var walkinPayGcash = document.getElementById('walkin_pay_gcash');
        if (walkinPayGcash) {
            walkinPayGcash.addEventListener('change', function () {
                famvilleSetWalkinPayMethod('gcash');
            });
        }
    });
</script>
@endpush
