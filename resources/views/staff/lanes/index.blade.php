@extends('layouts.app')

@section('title', 'Bowling Lanes')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0"><i class="bi bi-align-center"></i> Bowling Lanes</h3>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addLaneModal"><i class="bi bi-plus-circle"></i> Add Lane</button>
    </div>

    <div class="card shadow-sm table-responsive-card">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Price / Scoresheet</th>
                        <th>Status</th>
                        <th>Notes</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($lanes as $lane)
                        <tr>
                            <td>{{ $lane->name }}</td>
                            <td>&#8369;{{ number_format($lane->price_per_scoresheet, 2) }}</td>
                            <td><span class="badge bg-{{ $lane->status->color() }}">{{ $lane->status->label() }}</span></td>
                            <td class="text-muted small">{{ $lane->notes }}</td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editLaneModal{{ $lane->id }}">Edit</button>
                                <form method="POST" action="{{ route('staff.lanes.destroy', $lane) }}" class="d-inline" data-confirm="Delete {{ $lane->name }}?">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </td>
                        </tr>

                        <div class="modal fade" id="editLaneModal{{ $lane->id }}" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="POST" action="{{ route('staff.lanes.update', $lane) }}">
                                        @csrf @method('PUT')
                                        <div class="modal-header">
                                            <h5 class="modal-title">Edit {{ $lane->name }}</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label class="form-label">Name</label>
                                                <input type="text" name="name" class="form-control" value="{{ $lane->name }}" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Price per Scoresheet (₱)</label>
                                                <input type="number" step="0.01" min="0" name="price_per_scoresheet" class="form-control" value="{{ $lane->price_per_scoresheet }}" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Status</label>
                                                <select name="status" class="form-select">
                                                    @foreach (['available', 'occupied', 'reserved', 'maintenance'] as $status)
                                                        <option value="{{ $status }}" @selected($lane->status->value === $status)>{{ ucfirst($status) }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Notes</label>
                                                <textarea name="notes" class="form-control" rows="2">{{ $lane->notes }}</textarea>
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
                        <tr><td colspan="5" class="text-center text-muted py-4">No lanes yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal fade" id="addLaneModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('staff.lanes.store') }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Add Bowling Lane</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" name="name" class="form-control" placeholder="e.g. Lane 3" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Price per Scoresheet (₱)</label>
                            <input type="number" step="0.01" min="0" name="price_per_scoresheet" class="form-control" value="250" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="available" selected>Available</option>
                                <option value="maintenance">Maintenance</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Lane</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
