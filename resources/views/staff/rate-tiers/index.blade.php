@extends('layouts.app')

@section('title', 'Billiard Rate Tiers')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0"><i class="bi bi-tags"></i> Billiard Rate Tiers</h3>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTierModal"><i class="bi bi-plus-circle"></i> Add Rate Tier</button>
    </div>
    <p class="text-muted small">These time-based rates apply to all billiard tables. The rate used for a booking is whichever tier covers its start time.</p>

    <div class="card shadow-sm table-responsive-card">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Label</th>
                        <th>Start</th>
                        <th>End</th>
                        <th>Rate / Hour</th>
                        <th>Active</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rateTiers as $tier)
                        <tr>
                            <td>{{ $tier->label }}</td>
                            <td>{{ \Illuminate\Support\Carbon::parse($tier->start_time)->format('g:i A') }}</td>
                            <td>{{ \Illuminate\Support\Carbon::parse($tier->end_time)->format('g:i A') }}</td>
                            <td>&#8369;{{ number_format($tier->rate_per_hour, 2) }}</td>
                            <td><span class="badge bg-{{ $tier->is_active ? 'success' : 'secondary' }}">{{ $tier->is_active ? 'Active' : 'Inactive' }}</span></td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editTierModal{{ $tier->id }}">Edit</button>
                                <form method="POST" action="{{ route('staff.rate-tiers.destroy', $tier) }}" class="d-inline" data-confirm="Delete this rate tier?">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </td>
                        </tr>

                        <div class="modal fade" id="editTierModal{{ $tier->id }}" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="POST" action="{{ route('staff.rate-tiers.update', $tier) }}">
                                        @csrf @method('PUT')
                                        <div class="modal-header">
                                            <h5 class="modal-title">Edit {{ $tier->label }}</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label class="form-label">Label</label>
                                                <input type="text" name="label" class="form-control" value="{{ $tier->label }}" required>
                                            </div>
                                            <div class="row g-2">
                                                <div class="col">
                                                    <label class="form-label">Start Time</label>
                                                    <input type="time" name="start_time" class="form-control" value="{{ \Illuminate\Support\Carbon::parse($tier->start_time)->format('H:i') }}" required>
                                                </div>
                                                <div class="col">
                                                    <label class="form-label">End Time</label>
                                                    <input type="time" name="end_time" class="form-control" value="{{ \Illuminate\Support\Carbon::parse($tier->end_time)->format('H:i') }}" required>
                                                </div>
                                            </div>
                                            <div class="mb-3 mt-2">
                                                <label class="form-label">Rate per Hour (₱)</label>
                                                <input type="number" step="0.01" min="0" name="rate_per_hour" class="form-control" value="{{ $tier->rate_per_hour }}" required>
                                            </div>
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input" name="is_active" value="1" id="active{{ $tier->id }}" @checked($tier->is_active)>
                                                <label class="form-check-label" for="active{{ $tier->id }}">Active</label>
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
                        <tr><td colspan="6" class="text-center text-muted py-4">No rate tiers yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal fade" id="addTierModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('staff.rate-tiers.store') }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Add Rate Tier</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Label</label>
                            <input type="text" name="label" class="form-control" placeholder="e.g. Morning (10AM - 1PM)" required>
                        </div>
                        <div class="row g-2">
                            <div class="col">
                                <label class="form-label">Start Time</label>
                                <input type="time" name="start_time" class="form-control" required>
                            </div>
                            <div class="col">
                                <label class="form-label">End Time</label>
                                <input type="time" name="end_time" class="form-control" required>
                            </div>
                        </div>
                        <div class="mb-3 mt-2">
                            <label class="form-label">Rate per Hour (₱)</label>
                            <input type="number" step="0.01" min="0" name="rate_per_hour" class="form-control" required>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="is_active" value="1" id="newActive" checked>
                            <label class="form-check-label" for="newActive">Active</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Tier</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
