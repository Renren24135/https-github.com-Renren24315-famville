@php($mode = $mode ?? 'guest')

<div class="modal fade" id="availabilityModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-calendar-week"></i> <span id="availabilityAssetName">Availability</span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label for="availabilityDate" class="form-label">Date</label>
                <input type="date" id="availabilityDate" class="form-control mb-3" min="{{ now()->toDateString() }}" value="{{ now()->toDateString() }}">

                <div id="availabilityLoading" class="text-center text-muted small py-3 d-none">
                    <div class="spinner-border spinner-border-sm"></div> Loading timetable&hellip;
                </div>
                <div id="availabilityError" class="alert alert-danger small d-none"></div>

                <table class="table table-sm align-middle mb-0 d-none" id="availabilityTable">
                    <thead>
                        <tr><th>Hour</th><th class="text-end">Status</th></tr>
                    </thead>
                    <tbody id="availabilitySlots"></tbody>
                </table>

                <div class="d-flex flex-wrap gap-3 mt-3">
                    <span class="fv-status fv-status-success"><span class="fv-status-indicator"></span>Free</span>
                    <span class="fv-status fv-status-warning"><span class="fv-status-indicator"></span>Reserved</span>
                    <span class="fv-status fv-status-danger"><span class="fv-status-indicator"></span>In Use</span>
                    <span class="fv-status fv-status-info"><span class="fv-status-indicator"></span>Pending Approval</span>
                        <span class="fv-status fv-status-secondary"><span class="fv-status-indicator"></span>Unavailable</span>
                        <span class="fv-status fv-status-secondary"><span class="fv-status-indicator"></span>Closed</span>
                </div>

                @if ($mode === 'customer')
                    <div class="fv-lane-divider my-3"></div>
                    <a href="#" id="availabilityBookBtn" class="btn btn-primary w-100 d-none"><i class="bi bi-plus-circle"></i> Book This <span id="availabilityBookBtnLabel"></span></a>
                    <p id="availabilityBookNote" class="text-muted small mb-0 d-none"></p>
                @elseif ($mode === 'staff')
                    <div class="fv-lane-divider my-3"></div>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route('staff.pos.index') }}" class="btn btn-outline-primary flex-fill"><i class="bi bi-cash-register"></i> Open in POS</a>
                        <a href="{{ route('staff.bookings.index') }}" class="btn btn-outline-secondary flex-fill"><i class="bi bi-calendar-check"></i> Open in Bookings</a>
                    </div>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script nonce="{{ $cspNonce }}">
    var famvilleAvailabilityBaseUrl = {{ Js::from(route('availability.show', ['type' => '__TYPE__', 'id' => '__ID__'])) }};
    @if ($mode === 'customer')
        var famvilleBookingCreateBaseUrl = {{ Js::from(route('customer.bookings.create', ['asset_type' => '__TYPE__', 'asset_id' => '__ID__'])) }};
    @endif
    var famvilleAvailabilityType = null;
    var famvilleAvailabilityId = null;

    function famvilleShowAvailability(type, id, name, date, status) {
        famvilleAvailabilityType = type;
        famvilleAvailabilityId = id;

        document.getElementById('availabilityAssetName').textContent = name;
        document.getElementById('availabilityDate').value = date || '{{ now()->toDateString() }}';

        @if ($mode === 'customer')
            // Occupied/Reserved is only a snapshot of right now — still
            // bookable for a later slot today. Maintenance is the one true,
            // time-independent block (matches the server-side check in
            // Customer\BookingController::store()).
            var bookBtn = document.getElementById('availabilityBookBtn');
            var bookNote = document.getElementById('availabilityBookNote');
            if (status !== 'maintenance') {
                bookBtn.href = famvilleBookingCreateBaseUrl.replace('__TYPE__', type).replace('__ID__', id);
                document.getElementById('availabilityBookBtnLabel').textContent = type === 'lane' ? 'Lane' : 'Table';
                bookBtn.classList.remove('d-none');
                bookNote.classList.add('d-none');
            } else {
                bookBtn.classList.add('d-none');
                bookNote.textContent = 'This asset is under maintenance right now — check back later.';
                bookNote.classList.remove('d-none');
            }
        @endif

        var modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('availabilityModal'));
        modal.show();

        famvilleLoadAvailability();
    }

    function famvilleLoadAvailability() {
        if (!famvilleAvailabilityType || !famvilleAvailabilityId) {
            return;
        }

        var loading = document.getElementById('availabilityLoading');
        var errorBox = document.getElementById('availabilityError');
        var table = document.getElementById('availabilityTable');
        var tbody = document.getElementById('availabilitySlots');
        var date = document.getElementById('availabilityDate').value;

        loading.classList.remove('d-none');
        errorBox.classList.add('d-none');
        table.classList.add('d-none');
        tbody.innerHTML = '';

        var url = famvilleAvailabilityBaseUrl
            .replace('__TYPE__', famvilleAvailabilityType)
            .replace('__ID__', famvilleAvailabilityId)
            + '?date=' + encodeURIComponent(date);

        fetch(url, {
            headers: { 'Accept': 'application/json' },
        })
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

    document.addEventListener('DOMContentLoaded', function () {
        var dateInput = document.getElementById('availabilityDate');
        if (dateInput) {
            dateInput.addEventListener('change', famvilleLoadAvailability);
        }

        // Delegated so it works for every asset card on the page (lanes
        // and tables are rendered in separate loops, plus the floor-plan
        // tiles), without each one needing its own inline onclick.
        document.addEventListener('click', function (e) {
            var card = e.target.closest('[data-asset-type][data-asset-id]');
            if (!card) {
                return;
            }
            famvilleShowAvailability(card.dataset.assetType, card.dataset.assetId, card.dataset.assetName, undefined, card.dataset.assetStatus);
        });

        // role="button" tiles (facility cards, floor-plan tiles) are
        // keyboard-focusable but only wired for mouse clicks elsewhere —
        // this makes Enter/Space activate them too.
        document.addEventListener('keydown', function (e) {
            if (e.key !== 'Enter' && e.key !== ' ') {
                return;
            }
            var card = e.target.closest('[data-asset-type][data-asset-id]');
            if (!card) {
                return;
            }
            e.preventDefault();
            famvilleShowAvailability(card.dataset.assetType, card.dataset.assetId, card.dataset.assetName, undefined, card.dataset.assetStatus);
        });
    });
</script>
