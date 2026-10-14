@extends('layouts.app')

@section('title', 'Closures')

@section('content')
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <div class="fv-eyebrow mb-1">Venue Scheduling</div>
            <h3 class="mb-0"><i class="bi bi-calendar-x"></i> Closures</h3>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addClosureModal"><i class="bi bi-plus-circle"></i> Add Closure</button>
    </div>
    <p class="text-muted small mb-4"><i class="bi bi-info-circle"></i> Block out dates for tournaments, maintenance, or events &mdash; closed dates are rejected at booking/walk-in and shown as "Closed" on the timetable. A closure can't be saved over dates that already have bookings; resolve those first from Bookings.</p>

    <div class="card shadow-sm table-responsive-card">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Facility</th>
                        <th>Scope</th>
                        <th>Dates</th>
                        <th>Reason</th>
                        <th>Added By</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($closures as $closure)
                        <tr>
                            <td><i class="bi {{ $closure->isLane() ? 'bi-align-center' : 'bi-circle' }}"></i> {{ $closure->isLane() ? 'Bowling' : 'Billiards' }}</td>
                            <td>{{ $closure->assetScopeLabel() }}</td>
                            <td class="fv-mono">{{ $closure->start_date->format('M d, Y') }} &ndash; {{ $closure->end_date->format('M d, Y') }}</td>
                            <td>{{ $closure->reason }}</td>
                            <td class="text-muted small">{{ $closure->creator?->name ?? '—' }}</td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editClosureModal{{ $closure->id }}">Edit</button>
                                <form method="POST" action="{{ route('staff.closures.destroy', $closure) }}" class="d-inline" data-confirm="Remove this closure?">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </td>
                        </tr>

                        <div class="modal fade" id="editClosureModal{{ $closure->id }}" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="POST" action="{{ route('staff.closures.update', $closure) }}">
                                        @csrf @method('PUT')
                                        <div class="modal-header">
                                            <h5 class="modal-title">Edit Closure</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label class="form-label d-block">Facility</label>
                                                <div class="btn-group" role="group">
                                                    <input type="radio" class="btn-check fv-closure-facility" name="facility" id="facility_lane_{{ $closure->id }}" value="lane" data-closure-id="{{ $closure->id }}" @checked($closure->isLane())>
                                                    <label class="btn btn-outline-secondary" for="facility_lane_{{ $closure->id }}"><i class="bi bi-align-center"></i> Bowling</label>

                                                    <input type="radio" class="btn-check fv-closure-facility" name="facility" id="facility_table_{{ $closure->id }}" value="table" data-closure-id="{{ $closure->id }}" @checked(! $closure->isLane())>
                                                    <label class="btn btn-outline-secondary" for="facility_table_{{ $closure->id }}"><i class="bi bi-circle"></i> Billiards</label>
                                                </div>
                                            </div>
                                            <div class="mb-3" id="assetIdLaneWrap_{{ $closure->id }}" style="{{ $closure->isLane() ? '' : 'display:none' }}">
                                                <label class="form-label">Lane (leave blank to close all)</label>
                                                <select name="asset_id" class="form-select" {{ $closure->isLane() ? '' : 'disabled' }}>
                                                    <option value="">All Bowling Lanes</option>
                                                    @foreach ($lanes as $lane)
                                                        <option value="{{ $lane->id }}" @selected($closure->isLane() && $closure->bookable_id === $lane->id)>{{ $lane->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="mb-3" id="assetIdTableWrap_{{ $closure->id }}" style="{{ $closure->isLane() ? 'display:none' : '' }}">
                                                <label class="form-label">Table (leave blank to close all)</label>
                                                <select name="asset_id" class="form-select" {{ $closure->isLane() ? 'disabled' : '' }}>
                                                    <option value="">All Billiard Tables</option>
                                                    @foreach ($tables as $table)
                                                        <option value="{{ $table->id }}" @selected(! $closure->isLane() && $closure->bookable_id === $table->id)>{{ $table->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="row g-2">
                                                <div class="col">
                                                    <label class="form-label">Start Date</label>
                                                    <input type="date" name="start_date" class="form-control" value="{{ $closure->start_date->format('Y-m-d') }}" required>
                                                </div>
                                                <div class="col">
                                                    <label class="form-label">End Date</label>
                                                    <input type="date" name="end_date" class="form-control" value="{{ $closure->end_date->format('Y-m-d') }}" required>
                                                </div>
                                            </div>
                                            <div class="mb-3 mt-2">
                                                <label class="form-label">Reason</label>
                                                <textarea name="reason" class="form-control" rows="2" maxlength="255" required>{{ $closure->reason }}</textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-primary">Save</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">No closures scheduled.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal fade" id="addClosureModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('staff.closures.store') }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Add Closure</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label d-block">Facility</label>
                            <div class="btn-group" role="group">
                                <input type="radio" class="btn-check fv-closure-facility" name="facility" id="facility_lane_add" value="lane" data-closure-id="add" checked>
                                <label class="btn btn-outline-secondary" for="facility_lane_add"><i class="bi bi-align-center"></i> Bowling</label>

                                <input type="radio" class="btn-check fv-closure-facility" name="facility" id="facility_table_add" value="table" data-closure-id="add">
                                <label class="btn btn-outline-secondary" for="facility_table_add"><i class="bi bi-circle"></i> Billiards</label>
                            </div>
                        </div>
                        <div class="mb-3" id="assetIdLaneWrap_add">
                            <label class="form-label">Lane (leave blank to close all)</label>
                            <select name="asset_id" class="form-select">
                                <option value="">All Bowling Lanes</option>
                                @foreach ($lanes as $lane)
                                    <option value="{{ $lane->id }}">{{ $lane->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3" id="assetIdTableWrap_add" style="display:none">
                            <label class="form-label">Table (leave blank to close all)</label>
                            <select name="asset_id" class="form-select" disabled>
                                <option value="">All Billiard Tables</option>
                                @foreach ($tables as $table)
                                    <option value="{{ $table->id }}">{{ $table->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="row g-2">
                            <div class="col">
                                <label class="form-label">Start Date</label>
                                <input type="date" name="start_date" class="form-control" min="{{ now()->toDateString() }}" value="{{ old('start_date') }}" required>
                            </div>
                            <div class="col">
                                <label class="form-label">End Date</label>
                                <input type="date" name="end_date" class="form-control" min="{{ now()->toDateString() }}" value="{{ old('end_date') }}" required>
                            </div>
                        </div>
                        <div class="mb-3 mt-2">
                            <label class="form-label">Reason</label>
                            <textarea name="reason" class="form-control" rows="2" maxlength="255" placeholder="e.g. Used for tournament" required>{{ old('reason') }}</textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Closure</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce }}">
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.fv-closure-facility').forEach(function (radio) {
            radio.addEventListener('change', function () {
                var id = this.dataset.closureId;
                var isLane = this.value === 'lane';
                var laneWrap = document.getElementById('assetIdLaneWrap_' + id);
                var tableWrap = document.getElementById('assetIdTableWrap_' + id);

                laneWrap.style.display = isLane ? '' : 'none';
                tableWrap.style.display = isLane ? 'none' : '';
                laneWrap.querySelector('select').disabled = !isLane;
                tableWrap.querySelector('select').disabled = isLane;
            });
        });
    });
</script>
@endpush
