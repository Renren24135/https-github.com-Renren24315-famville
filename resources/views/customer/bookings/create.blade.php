@extends('layouts.app')

@section('title', 'Book a Slot')

@section('content')
    <h3 class="mb-1"><i class="bi bi-plus-circle"></i> Book a Session</h3>
    <p class="text-muted mb-4">Pick your game, then follow the guided steps to confirm your slot.</p>

    <div class="alert alert-info small" style="max-width: 700px;">
        <i class="bi bi-info-circle"></i> Online reservations require a <strong>reservation fee</strong> (10% of the price, or a flat &#8369;20 for Billiards Open Time), paid upfront via GCash, to hold your slot. The remaining balance is paid separately when you arrive. Walk-ins don't pay this fee.
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('customer.bookings.store') }}" id="bookingForm">
                @csrf

                <div class="fv-step mb-3">
                    <span class="fv-step-num">01</span>
                    <span class="fv-step-label">Choose Your Game &amp; Facility</span>
                </div>
                <ul class="nav nav-pills mb-3" id="assetTypeTabs">
                    <li class="nav-item">
                        <button class="nav-link active" type="button" id="assetTypeLaneBtn" data-bs-toggle="pill" data-bs-target="#laneTab">
                            <i class="bi bi-align-center"></i> Bowling Lane
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" type="button" id="assetTypeTableBtn" data-bs-toggle="pill" data-bs-target="#tableTab">
                            <i class="bi bi-circle"></i> Billiard Table
                        </button>
                    </li>
                </ul>
                <input type="hidden" name="asset_type" id="asset_type" value="lane">

                <div class="tab-content mb-2">
                    <div class="tab-pane fade show active" id="laneTab">
                        <label class="form-label">Choose a Lane</label>
                        @error('lane_id')<div class="text-danger small mb-2">{{ $message }}</div>@enderror
                        <div class="row g-2">
                            @foreach ($lanes as $lane)
                                {{-- Occupied/Reserved is just a live snapshot of right now, not of
                                     whatever date/time gets picked next — still selectable so a
                                     busy-right-now lane can be booked for a later slot today.
                                     Only Maintenance is a true, time-independent block. A closure
                                     covering today is date-specific too (the wizard's date picker
                                     + timetable + server validation already enforce the real,
                                     per-date block) — so it's shown, not disabled, same reasoning. --}}
                                @php($isBookable = $lane->status->value !== 'maintenance')
                                @php($closure = \App\Models\AssetClosure::closing($lane, now()->toDateString()))
                                <div class="col-6 col-md-4">
                                    <input type="radio" class="btn-check" name="lane_id" id="lane_{{ $lane->id }}" value="{{ $lane->id }}" data-name="{{ $lane->name }}"
                                        {{ ! $isBookable ? 'disabled' : '' }} {{ old('lane_id') == $lane->id ? 'checked' : '' }} required>
                                    <label class="btn btn-outline-secondary w-100 h-100 text-center py-2" for="lane_{{ $lane->id }}">
                                        {{ $lane->name }}<br>
                                        <small class="fv-mono">&#8369;{{ number_format($lane->price_per_scoresheet, 2) }}/scoresheet</small><br>
                                        @if ($closure)
                                            <span class="badge bg-danger">Closed</span>
                                            <br><small class="text-muted">today &mdash; pick another date</small>
                                        @else
                                            <span class="badge bg-{{ $lane->status->color() }}">{{ $lane->status->label() }}</span>
                                            @if ($lane->status->value !== 'available' && $isBookable)
                                                <br><small class="text-muted">pick a later time</small>
                                            @endif
                                        @endif
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="tab-pane fade" id="tableTab">
                        <label class="form-label">Choose a Table</label>
                        @error('table_id')<div class="text-danger small mb-2">{{ $message }}</div>@enderror
                        <div class="row g-2">
                            @foreach ($tables as $table)
                                @php($isBookable = $table->status->value !== 'maintenance')
                                @php($closure = \App\Models\AssetClosure::closing($table, now()->toDateString()))
                                <div class="col-6 col-md-4">
                                    <input type="radio" class="btn-check" name="table_id" id="table_{{ $table->id }}" value="{{ $table->id }}" data-name="{{ $table->name }}"
                                        {{ ! $isBookable ? 'disabled' : '' }} {{ old('table_id') == $table->id ? 'checked' : '' }} {{ old('asset_type') === 'table' ? 'required' : '' }}>
                                    <label class="btn btn-outline-secondary w-100 h-100 text-center py-2" for="table_{{ $table->id }}">
                                        {{ $table->name }}<br>
                                        @if ($closure)
                                            <span class="badge bg-danger">Closed</span>
                                            <br><small class="text-muted">today &mdash; pick another date</small>
                                        @else
                                            <span class="badge bg-{{ $table->status->color() }}">{{ $table->status->label() }}</span>
                                            @if ($table->status->value !== 'available' && $isBookable)
                                                <br><small class="text-muted">pick a later time</small>
                                            @endif
                                        @endif
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                @error('asset_type')<div class="text-danger small mt-1">{{ $message }}</div>@enderror

                <p class="text-muted small mb-0 mt-2"><i class="bi bi-hand-index-thumb"></i> Click a lane/table above to check its availability and complete your booking.</p>
            </form>
        </div>
    </div>

    <div class="modal fade" id="bookingWizardModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-scrollable modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-calendar-week"></i> <span id="wizardAssetName">Book</span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="fv-step mb-3">
                        <span class="fv-step-num">02</span>
                        <span class="fv-step-label">Date &amp; Time</span>
                    </div>
                    <div class="row g-3 mb-2">
                        <div class="col-md-6">
                            <label for="booking_date" class="form-label">Date</label>
                            <input type="date" name="booking_date" id="booking_date" form="bookingForm"
                                class="form-control @error('booking_date') is-invalid @enderror" min="{{ now()->toDateString() }}"
                                value="{{ old('booking_date', now()->toDateString()) }}" required>
                            @error('booking_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="start_time" class="form-label">Start Time</label>
                            <input type="time" name="start_time" id="start_time" form="bookingForm"
                                class="form-control @error('start_time') is-invalid @enderror" value="{{ old('start_time') }}" min="14:00" max="22:00" required>
                            <div class="form-text">Open 2:00 PM - 10:00 PM daily.</div>
                            @error('start_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div id="wizardAvailLoading" class="text-center text-muted small py-3 d-none">
                        <div class="spinner-border spinner-border-sm"></div> Loading timetable&hellip;
                    </div>
                    <div id="wizardAvailError" class="alert alert-danger small d-none"></div>
                    <table class="table table-sm align-middle mb-0 d-none" id="wizardAvailTable">
                        <thead>
                            <tr><th>Hour</th><th class="text-end">Status</th></tr>
                        </thead>
                        <tbody id="wizardAvailSlots"></tbody>
                    </table>
                    <div class="d-flex gap-3 small text-muted mt-2 flex-wrap">
                        <span class="fv-status fv-status-success"><span class="fv-status-indicator"></span>Free</span>
                        <span class="fv-status fv-status-warning"><span class="fv-status-indicator"></span>Reserved</span>
                        <span class="fv-status fv-status-danger"><span class="fv-status-indicator"></span>In Use</span>
                        <span class="fv-status fv-status-info"><span class="fv-status-indicator"></span>Pending Approval</span>
                        <span class="fv-status fv-status-secondary"><span class="fv-status-indicator"></span>Unavailable</span>
                        <span class="fv-status fv-status-secondary"><span class="fv-status-indicator"></span>Closed</span>
                    </div>

                    <x-fv-lane-divider />
                    <div class="fv-step mb-3">
                        <span class="fv-step-num">03</span>
                        <span class="fv-step-label">Reservation Payment</span>
                    </div>

                    <div class="alert alert-info small mb-3"><i class="bi bi-lightning-charge"></i> You'll choose how to pay when you confirm at the end — <strong>Pay via GCash Now</strong> for instant, automatic confirmation through PayMongo, or <strong>Confirm &amp; Pay Manually</strong> if you've already sent the fee yourself and just need to enter the reference number below.</div>

                    <p class="mb-2"><strong>Pay the reservation fee via GCash</strong></p>
                    <p class="text-muted small mb-2">
                        <i class="bi bi-calculator"></i> For a fixed-price booking, the fee is 10% of your session's total price:
                        <br>&#8226; Bowling (&#8369;250/scoresheet) &mdash; 10% of &#8369;250 is &#8369;25.
                        <br>&#8226; Billiards Fixed Hours (&#8369;50/hr afternoon, &#8369;100/hr evening) &mdash; 10% of &#8369;50 is &#8369;5, 10% of &#8369;100 is &#8369;10.
                    </p>
                    <p class="text-muted small mb-2" id="openTableFeeNote">
                        <i class="bi bi-info-circle"></i> Billiards Open Time has no known price upfront, so a flat &#8369;20 reservation fee applies instead &mdash; still required before your booking can be approved.
                    </p>
                    @error('reservation_fee_method')<div class="text-danger small mb-2">{{ $message }}</div>@enderror
                    <input type="hidden" name="reservation_fee_method" id="reservation_fee_method" form="bookingForm" value="gcash">

                    <div class="border rounded p-3 mb-3 bg-light" style="max-width: 360px;">
                        <div class="text-center mb-2">
                            <p class="mb-2 small text-muted">Scan to send your reservation fee via GCash:</p>
                            <img src="{{ $gcash['qr_image'] }}" alt="GCash QR code" class="img-fluid rounded mb-2" style="max-width: 240px;">
                            <div class="fs-5 fw-bold">{{ $gcash['number'] }}</div>
                            <div class="text-muted">{{ $gcash['account_name'] }}</div>
                        </div>
                        <label for="reservation_fee_reference" class="form-label">GCash Reference Number</label>
                        <input type="text" name="reservation_fee_reference" id="reservation_fee_reference" form="bookingForm" required
                            class="form-control @error('reservation_fee_reference') is-invalid @enderror" value="{{ old('reservation_fee_reference') }}" placeholder="e.g. 1234567890123">
                        @error('reservation_fee_reference')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">After sending, enter the reference number from your GCash receipt. We'll verify it before approving your reservation.</div>
                    </div>

                    <x-fv-lane-divider />
                    <div class="fv-step mb-3">
                        <span class="fv-step-num">03</span>
                        <span class="fv-step-label">Session Price</span>
                    </div>
                    <p class="mb-2"><strong>Where do you plan to pay for the session itself?</strong></p>
                    @error('balance_payment_method')<div class="text-danger small mb-2">{{ $message }}</div>@enderror
                    <div class="btn-group mb-3" role="group">
                        <input type="radio" class="btn-check" name="balance_payment_method" id="balance_gcash" value="gcash" form="bookingForm"
                            {{ old('balance_payment_method', 'walkin') === 'gcash' ? 'checked' : '' }} required>
                        <label class="btn btn-outline-secondary" for="balance_gcash"><i class="bi bi-phone"></i> GCash</label>

                        <input type="radio" class="btn-check" name="balance_payment_method" id="balance_walkin" value="walkin" form="bookingForm"
                            {{ old('balance_payment_method', 'walkin') === 'walkin' ? 'checked' : '' }} required>
                        <label class="btn btn-outline-secondary" for="balance_walkin"><i class="bi bi-shop"></i> Walk-in (Cash)</label>
                    </div>

                    <div id="balanceGcashBox" class="border rounded p-3 mb-3 bg-light d-none" style="max-width: 360px;">
                        <div class="text-center mb-2">
                            <p class="mb-2 small text-muted">Same GCash you'll send the balance to when you arrive:</p>
                            <img src="{{ $gcash['qr_image'] }}" alt="GCash QR code" class="img-fluid rounded mb-2" style="max-width: 240px;">
                            <div class="fs-5 fw-bold">{{ $gcash['number'] }}</div>
                            <div class="text-muted">{{ $gcash['account_name'] }}</div>
                        </div>
                        <label for="balance_payment_reference" class="form-label">GCash Reference Number <span class="text-muted">(optional)</span></label>
                        <input type="text" name="balance_payment_reference" id="balance_payment_reference" form="bookingForm"
                            class="form-control @error('balance_payment_reference') is-invalid @enderror" value="{{ old('balance_payment_reference') }}" placeholder="e.g. 1234567890123">
                        @error('balance_payment_reference')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">Already sent it ahead of time? Enter the reference number here. Otherwise, leave blank and pay when you arrive.</div>
                    </div>

                    <p class="text-muted small">Just your preference for now &mdash; the actual amount and payment happen at the counter when you arrive.</p>

                    <x-fv-lane-divider />
                    <div class="fv-step mb-3">
                        <span class="fv-step-num">04</span>
                        <span class="fv-step-label">How Much / How Long</span>
                    </div>

                    <div id="laneQuantitySection">
                        <label for="scoresheets" class="form-label">Number of Scoresheets</label>
                        <input type="number" min="1" max="20" name="scoresheets" id="scoresheets" form="bookingForm" class="form-control" style="max-width:200px" value="{{ old('scoresheets', 1) }}">
                    </div>

                    <div id="tableQuantitySection" class="d-none">
                        <label class="form-label d-block">How would you like to play?</label>
                        @error('duration_mode')<div class="text-danger small mb-2">{{ $message }}</div>@enderror
                        <div class="btn-group" role="group">
                            <input type="radio" class="btn-check" name="duration_mode" id="mode_fixed" value="fixed" form="bookingForm" {{ old('duration_mode', 'fixed') === 'fixed' ? 'checked' : '' }}>
                            <label class="btn btn-outline-secondary" for="mode_fixed">Fixed Hours</label>

                            <input type="radio" class="btn-check" name="duration_mode" id="mode_open" value="open" form="bookingForm" {{ old('duration_mode') === 'open' ? 'checked' : '' }}>
                            <label class="btn btn-outline-secondary" for="mode_open">Open Time</label>
                        </div>

                        <div class="mt-3" id="fixedHoursWrap">
                            <label for="hours" class="form-label">Number of Hours</label>
                            <input type="number" min="1" max="8" name="hours" id="hours" form="bookingForm" class="form-control" style="max-width:200px" value="{{ old('hours', 1) }}">
                            @error('hours')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
                        <div class="text-muted small mt-2 d-none" id="openTimeNote">
                            <i class="bi bi-stopwatch"></i> No fixed duration &mdash; play as long as you like starting at your chosen hour. You're billed hourly for actual time played (any started hour counts as a full hour), at the rate for the window you start in.
                        </div>
                    </div>

                    <x-fv-lane-divider />
                    <div class="fv-step mb-3">
                        <span class="fv-step-num">05</span>
                        <span class="fv-step-label">Review &amp; Confirm</span>
                    </div>

                    <div class="fv-next-session mb-3">
                        <div class="fv-eyebrow mb-2"><i class="bi bi-clipboard-check"></i> Booking Summary</div>
                        <dl class="row mb-0 small">
                            <dt class="col-4 text-muted">Where</dt>
                            <dd class="col-8" id="summaryWhere">&mdash;</dd>
                            <dt class="col-4 text-muted">When</dt>
                            <dd class="col-8" id="summaryWhen">&mdash;</dd>
                            <dt class="col-4 text-muted">What</dt>
                            <dd class="col-8" id="summaryWhat">&mdash;</dd>
                            <dt class="col-4 text-muted">How Much</dt>
                            <dd class="col-8 fv-mono" id="summaryAmount">&mdash;</dd>
                        </dl>
                    </div>

                    <p>Before confirming your online reservation, please review:</p>
                    <ul class="small">
                        <li>A <strong>reservation fee</strong> applies on top of your session cost, since online bookings hold a lane/table for you in advance &mdash; 10% of the price, or a flat &#8369;20 for Billiards Open Time. Walk-ins don't pay this fee.</li>
                        <li>The reservation fee is <strong>required upfront</strong> and <strong>non-refundable</strong> &mdash; your booking is only approved once it's confirmed received.</li>
                        <li>The remaining balance is paid separately, at the counter, when you arrive.</li>
                        <li>Once approved, you must arrive within <strong>10 minutes</strong> of your scheduled start time, or your reservation will be cancelled and the lane/table released.</li>
                        <li>If you paid via GCash, please <strong>save a screenshot of your payment</strong> and bring a valid <strong>ID or account matching the sender's name</strong> when you arrive, so the cashier can verify you're the one who made the reservation.</li>
                    </ul>
                    <div class="form-check mt-3">
                        <input type="checkbox" class="form-check-input" id="agreeTerms">
                        <label class="form-check-label" for="agreeTerms">I have read and agree to the reservation fee and terms above.</label>
                    </div>
                </div>
                <div class="modal-footer flex-wrap">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <input type="hidden" name="pay_via_paymongo" id="pay_via_paymongo" form="bookingForm" value="0">
                    <button type="button" class="btn btn-outline-primary" id="confirmBookingBtn" disabled>
                        <i class="bi bi-check-circle"></i> Confirm &amp; Pay Manually
                    </button>
                    <button type="button" class="btn btn-success" id="payViaPaymongoBtn" disabled>
                        <i class="bi bi-phone"></i> Pay via GCash Now (Instant)
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce }}">
    var famvilleWizardType = null;
    var famvilleWizardId = null;
    var famvilleWizardName = '';
    var famvilleAvailabilityBaseUrl = {{ Js::from(route('availability.show', ['type' => '__TYPE__', 'id' => '__ID__'])) }};
    // Lane prices only — billiard rates are time-tier-dependent and not
    // passed to this view, so the summary shows an honest "confirmed at
    // checkout" note for tables instead of guessing a rate client-side.
    var famvilleLanePrices = {{ Js::from($lanes->pluck('price_per_scoresheet', 'id')) }};

    function famvilleUpdateSummary() {
        var isLane = document.getElementById('asset_type').value === 'lane';
        var date = document.getElementById('booking_date').value;
        var time = document.getElementById('start_time').value;

        document.getElementById('summaryWhere').textContent = famvilleWizardName || '—';

        var whenParts = [];
        if (date) { whenParts.push(new Date(date + 'T00:00:00').toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })); }
        if (time) {
            var t = new Date('2000-01-01T' + time);
            whenParts.push(t.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' }));
        }
        document.getElementById('summaryWhen').textContent = whenParts.length ? whenParts.join(' · ') : '—';

        var amountEl = document.getElementById('summaryAmount');

        if (isLane) {
            var qty = parseInt(document.getElementById('scoresheets').value, 10) || 0;
            document.getElementById('summaryWhat').textContent = qty + ' scoresheet' + (qty === 1 ? '' : 's');

            var rate = parseFloat(famvilleLanePrices[document.querySelector('input[name="lane_id"]:checked')?.value]) || 0;
            var base = rate * qty;
            var fee = Math.round(base * 0.10 * 100) / 100;
            amountEl.textContent = qty > 0
                ? '₱' + (base + fee).toLocaleString('en-US', { minimumFractionDigits: 2 }) + ' (incl. ₱' + fee.toLocaleString('en-US', { minimumFractionDigits: 2 }) + ' fee)'
                : '—';
        } else {
            var mode = (document.querySelector('input[name="duration_mode"]:checked') || {}).value || 'fixed';
            if (mode === 'open') {
                document.getElementById('summaryWhat').textContent = 'Open Time';
                amountEl.textContent = '₱20.00 fee now, rest at checkout';
            } else {
                var hours = parseInt(document.getElementById('hours').value, 10) || 0;
                document.getElementById('summaryWhat').textContent = hours + ' hour' + (hours === 1 ? '' : 's');
                amountEl.textContent = 'Confirmed at checkout, based on your start time\'s rate';
            }
        }
    }

    function famvilleSetAssetType(type) {
        document.getElementById('asset_type').value = type;
        var isLane = type === 'lane';

        document.getElementById('laneQuantitySection').classList.toggle('d-none', !isLane);
        document.getElementById('tableQuantitySection').classList.toggle('d-none', isLane);

        document.getElementById('scoresheets').required = isLane;
        document.getElementById('scoresheets').disabled = !isLane;

        document.querySelectorAll('input[name="lane_id"]').forEach(function (el) { el.required = isLane; });
        document.querySelectorAll('input[name="table_id"]').forEach(function (el) { el.required = !isLane; });

        // The "Fixed Hours" radio stays checked by default even while the
        // Lane tab is active — disable the whole duration_mode group for a
        // lane booking so it never submits a stray value the server would
        // otherwise (wrongly) read as "this table wants Fixed Hours".
        document.querySelectorAll('input[name="duration_mode"]').forEach(function (el) { el.disabled = isLane; });

        famvilleSetDurationMode(isLane ? null : (document.querySelector('input[name="duration_mode"]:checked') || {}).value || 'fixed');
        famvilleUpdateSummary();
    }

    function famvilleSetDurationMode(mode) {
        var isTable = document.getElementById('asset_type').value === 'table';
        var isFixed = isTable && mode === 'fixed';
        var isOpen = isTable && mode === 'open';

        document.getElementById('fixedHoursWrap').classList.toggle('d-none', !isFixed);
        document.getElementById('openTimeNote').classList.toggle('d-none', !isOpen);
        document.getElementById('hours').required = isFixed;
        document.getElementById('hours').disabled = !isFixed;

        famvilleUpdateFeeSection();
        famvilleUpdateSummary();
    }

    function famvilleUpdateFeeSection() {
        var isTable = document.getElementById('asset_type').value === 'table';
        var mode = (document.querySelector('input[name="duration_mode"]:checked') || {}).value || 'fixed';
        var isOpenTable = isTable && mode === 'open';

        // The fee section always stays visible — every booking, Open Time
        // included, now requires the upfront GCash fee; only the flat-fee
        // explainer note is specific to Open Time.
        document.getElementById('openTableFeeNote').classList.toggle('d-none', !isOpenTable);
    }

    function famvilleSetBalanceMethod(method) {
        document.getElementById('balanceGcashBox').classList.toggle('d-none', method !== 'gcash');
    }

    function famvilleOpenBookingWizard(type, id, name) {
        famvilleWizardType = type;
        famvilleWizardId = id;
        famvilleWizardName = name;

        document.getElementById('wizardAssetName').textContent = name;

        // Reset the agreement checkbox each time the popup opens, so a
        // previous agreement doesn't silently carry over to a new selection.
        document.getElementById('agreeTerms').checked = false;
        document.getElementById('confirmBookingBtn').disabled = true;
        document.getElementById('payViaPaymongoBtn').disabled = true;

        var modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('bookingWizardModal'));
        modal.show();

        famvilleLoadWizardAvailability();
        famvilleUpdateSummary();
    }

    function famvilleLoadWizardAvailability() {
        if (!famvilleWizardType || !famvilleWizardId) {
            return;
        }

        var loading = document.getElementById('wizardAvailLoading');
        var errorBox = document.getElementById('wizardAvailError');
        var table = document.getElementById('wizardAvailTable');
        var tbody = document.getElementById('wizardAvailSlots');
        var date = document.getElementById('booking_date').value;

        loading.classList.remove('d-none');
        errorBox.classList.add('d-none');
        table.classList.add('d-none');
        tbody.innerHTML = '';

        var url = famvilleAvailabilityBaseUrl
            .replace('__TYPE__', famvilleWizardType)
            .replace('__ID__', famvilleWizardId)
            + '?date=' + encodeURIComponent(date);

        fetch(url, { headers: { 'Accept': 'application/json' } })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('Could not load the timetable for that date.');
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

    function famvilleConfirmBooking() {
        var form = document.getElementById('bookingForm');

        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        var btn = document.getElementById('confirmBookingBtn');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Submitting…';

        form.requestSubmit();
    }

    function famvillePayViaPaymongo() {
        var form = document.getElementById('bookingForm');
        var refInput = document.getElementById('reservation_fee_reference');

        document.getElementById('pay_via_paymongo').value = '1';
        // A manual reference number doesn't apply to the instant PayMongo
        // flow — relax the HTML5 requirement just for this submission so
        // the browser doesn't block it on an empty field the server itself
        // no longer requires when pay_via_paymongo is set (see
        // StoreBookingRequest).
        refInput.required = false;

        if (!form.checkValidity()) {
            form.reportValidity();
            document.getElementById('pay_via_paymongo').value = '0';
            refInput.required = true;
            return;
        }

        var btn = document.getElementById('payViaPaymongoBtn');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Redirecting to GCash…';

        form.requestSubmit();
    }
</script>
<script nonce="{{ $cspNonce }}">
    // Set the initial state once the DOM (including old() values) is ready.
    document.addEventListener('DOMContentLoaded', function () {
        famvilleSetAssetType((document.getElementById('asset_type') || {}).value || 'lane');
        famvilleSetBalanceMethod((document.querySelector('input[name="balance_payment_method"]:checked') || {}).value || 'walkin');

        // Wired here instead of inline onclick/onchange attributes (CSP).
        document.getElementById('assetTypeLaneBtn').addEventListener('click', function () {
            famvilleSetAssetType('lane');
        });
        document.getElementById('assetTypeTableBtn').addEventListener('click', function () {
            famvilleSetAssetType('table');
        });

        document.querySelectorAll('input[name="lane_id"]').forEach(function (input) {
            input.addEventListener('change', function () {
                famvilleOpenBookingWizard('lane', this.value, this.dataset.name);
            });
        });
        document.querySelectorAll('input[name="table_id"]').forEach(function (input) {
            input.addEventListener('change', function () {
                famvilleOpenBookingWizard('table', this.value, this.dataset.name);
            });
        });

        document.getElementById('booking_date').addEventListener('change', famvilleLoadWizardAvailability);
        document.getElementById('booking_date').addEventListener('change', famvilleUpdateSummary);
        document.getElementById('start_time').addEventListener('change', famvilleUpdateSummary);
        document.getElementById('scoresheets').addEventListener('input', famvilleUpdateSummary);
        document.getElementById('hours').addEventListener('input', famvilleUpdateSummary);

        document.getElementById('balance_gcash').addEventListener('change', function () {
            famvilleSetBalanceMethod('gcash');
        });
        document.getElementById('balance_walkin').addEventListener('change', function () {
            famvilleSetBalanceMethod('walkin');
        });

        document.getElementById('mode_fixed').addEventListener('change', function () {
            famvilleSetDurationMode('fixed');
        });
        document.getElementById('mode_open').addEventListener('change', function () {
            famvilleSetDurationMode('open');
        });

        document.getElementById('agreeTerms').addEventListener('change', function () {
            document.getElementById('confirmBookingBtn').disabled = !this.checked;
            document.getElementById('payViaPaymongoBtn').disabled = !this.checked;
        });

        document.getElementById('confirmBookingBtn').addEventListener('click', famvilleConfirmBooking);
        document.getElementById('payViaPaymongoBtn').addEventListener('click', famvillePayViaPaymongo);

        @if ($errors->any())
            // A rejected submission redirects back here with old() input —
            // re-open the wizard on the asset that was picked so the errors
            // (now inline inside the modal) are actually visible again,
            // instead of silently sitting behind a closed popup.
            var oldType = {{ Js::from(old('asset_type', 'lane')) }};
            var oldLaneId = {{ Js::from(old('lane_id')) }};
            var oldTableId = {{ Js::from(old('table_id')) }};
            var oldId = oldType === 'table' ? oldTableId : oldLaneId;

            if (oldType === 'table') {
                bootstrap.Tab.getOrCreateInstance(document.querySelector('#assetTypeTabs [data-bs-target="#tableTab"]')).show();
                famvilleSetAssetType('table');
            }

            if (oldId) {
                var select = document.querySelector('input[name="' + (oldType === 'table' ? 'table_id' : 'lane_id') + '"][value="' + oldId + '"]');
                var name = select ? select.dataset.name : '';
                famvilleOpenBookingWizard(oldType, oldId, name);
            }
        @elseif ($prefillType && $prefillId)
            // Carried over from the venue floor plan ("Book This Lane/
            // Table") — same mechanics as the old() restore above, just
            // triggered by a query string instead of a failed submit.
            var preType = {{ Js::from($prefillType) }};
            var preId = {{ Js::from((string) $prefillId) }};

            if (preType === 'table') {
                bootstrap.Tab.getOrCreateInstance(document.querySelector('#assetTypeTabs [data-bs-target="#tableTab"]')).show();
                famvilleSetAssetType('table');
            }

            var preInput = document.querySelector('input[name="' + (preType === 'table' ? 'table_id' : 'lane_id') + '"][value="' + preId + '"]');
            if (preInput && !preInput.disabled) {
                preInput.checked = true;
                famvilleOpenBookingWizard(preType, preId, preInput.dataset.name);
            }
        @endif
    });
</script>
@endpush
