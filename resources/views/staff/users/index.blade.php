@extends('layouts.app')

@section('title', 'Staff Accounts')

@section('content')
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <div class="fv-eyebrow mb-1">Team</div>
            <h3 class="mb-0"><i class="bi bi-people"></i> Staff Accounts</h3>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal"><i class="bi bi-person-plus"></i> Add Staff</button>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <x-fv-stat-tile label="Total Staff" :value="$users->count()" />
        </div>
        <div class="col-6 col-md-3">
            <x-fv-stat-tile label="Admins" :value="$users->filter(fn ($u) => $u->hasRole('admin'))->count()" />
        </div>
        <div class="col-6 col-md-3">
            <x-fv-stat-tile label="Cashiers" :value="$users->filter(fn ($u) => $u->hasRole('cashier'))->count()" />
        </div>
        <div class="col-6 col-md-3">
            <x-fv-stat-tile label="Active" :value="$users->where('is_active', true)->count()" />
        </div>
    </div>

    <div class="card shadow-sm table-responsive-card">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="d-inline-flex align-items-center justify-content-center rounded-circle fw-semibold fv-mono"
                                          style="width: 32px; height: 32px; background: var(--fv-surface-elevated); border: 1px solid var(--fv-border-strong); font-size: .75rem;">
                                        {{ strtoupper(substr($user->name, 0, 1)) }}
                                    </span>
                                    <span class="fw-semibold">{{ $user->name }}</span>
                                </div>
                            </td>
                            <td>{{ $user->email }}</td>
                            <td>{{ $user->phone }}</td>
                            <td><x-fv-status-dot :color="$user->hasRole('admin') ? 'info' : 'secondary'" :label="ucfirst($user->getRoleNames()->first())" /></td>
                            <td><x-fv-status-dot :color="$user->is_active ? 'success' : 'secondary'" :label="$user->is_active ? 'Active' : 'Inactive'" /></td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editUserModal{{ $user->id }}">Edit</button>
                                @if ($user->id !== auth()->id())
                                    <form method="POST" action="{{ route('staff.users.destroy', $user) }}" class="d-inline" data-confirm="Remove {{ $user->name }}'s account?">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                @endif
                            </td>
                        </tr>

                        <div class="modal fade" id="editUserModal{{ $user->id }}" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="POST" action="{{ route('staff.users.update', $user) }}">
                                        @csrf @method('PUT')
                                        <div class="modal-header">
                                            <h5 class="modal-title">Edit {{ $user->name }}</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label class="form-label">Name</label>
                                                <input type="text" name="name" class="form-control" value="{{ $user->name }}" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Email</label>
                                                <input type="email" name="email" class="form-control" value="{{ $user->email }}" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Phone</label>
                                                <input type="text" name="phone" class="form-control" value="{{ $user->phone }}">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Role</label>
                                                <select name="role" class="form-select">
                                                    <option value="admin" @selected($user->hasRole('admin'))>Admin</option>
                                                    <option value="cashier" @selected($user->hasRole('cashier'))>Cashier</option>
                                                </select>
                                            </div>
                                            <div class="form-check mb-3">
                                                <input type="checkbox" class="form-check-input" name="is_active" value="1" id="active{{ $user->id }}" @checked($user->is_active)>
                                                <label class="form-check-label" for="active{{ $user->id }}">Active</label>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">New Password <span class="text-muted">(leave blank to keep current)</span></label>
                                                <input type="password" name="password" class="form-control">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Confirm New Password</label>
                                                <input type="password" name="password_confirmation" class="form-control">
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
                        <tr><td colspan="6" class="text-center text-muted py-4">No staff accounts yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal fade" id="addUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('staff.users.store') }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Add Staff Account</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Role</label>
                            <select name="role" class="form-select">
                                <option value="cashier">Cashier</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required>
                            <div class="form-text">At least 8 characters, with upper &amp; lower case, a number, and a symbol.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Confirm Password</label>
                            <input type="password" name="password_confirmation" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create Account</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
