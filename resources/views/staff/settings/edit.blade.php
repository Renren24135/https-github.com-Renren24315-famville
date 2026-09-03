@extends('layouts.app')

@section('title', 'Settings')

@section('content')
    <h3 class="mb-4"><i class="bi bi-gear"></i> Company Settings</h3>

    <div class="card shadow-sm" style="max-width: 600px;">
        <div class="card-body">
            <form method="POST" action="{{ route('staff.settings.update') }}">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label class="form-label">Company Name</label>
                    <input type="text" name="company_name" class="form-control @error('company_name') is-invalid @enderror" value="{{ old('company_name', $settings['company_name']) }}" required>
                    @error('company_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Address</label>
                    <input type="text" name="company_address" class="form-control @error('company_address') is-invalid @enderror" value="{{ old('company_address', $settings['company_address']) }}" required>
                    @error('company_address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Phone</label>
                    <input type="text" name="company_phone" class="form-control @error('company_phone') is-invalid @enderror" value="{{ old('company_phone', $settings['company_phone']) }}" required>
                    @error('company_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="company_email" class="form-control @error('company_email') is-invalid @enderror" value="{{ old('company_email', $settings['company_email']) }}" required>
                    @error('company_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Facebook Page <span class="text-muted">(optional)</span></label>
                    <input type="text" name="company_facebook" class="form-control @error('company_facebook') is-invalid @enderror" value="{{ old('company_facebook', $settings['company_facebook']) }}">
                    @error('company_facebook')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Opening Hours</label>
                    <input type="text" name="opening_hours" class="form-control @error('opening_hours') is-invalid @enderror" value="{{ old('opening_hours', $settings['opening_hours']) }}" required>
                    @error('opening_hours')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <button type="submit" class="btn btn-primary">Save Settings</button>
            </form>
        </div>
    </div>
@endsection
