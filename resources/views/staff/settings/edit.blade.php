@extends('layouts.app')

@section('title', 'Settings')

@section('content')
    <h3 class="mb-4"><i class="bi bi-gear"></i> Company Settings</h3>

    <div class="card shadow-sm" style="max-width: 600px;">
        <div class="card-body">
            <form method="POST" action="{{ route('staff.settings.update') }}" enctype="multipart/form-data">
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

                <hr>
                <h6 class="text-uppercase text-muted small mb-3"><i class="bi bi-wallet2"></i> GCash Reservation Fee</h6>
                <div class="mb-3">
                    <label class="form-label">GCash Number</label>
                    <input type="text" name="gcash_number" class="form-control @error('gcash_number') is-invalid @enderror" placeholder="0917-000-0000" value="{{ old('gcash_number', $settings['gcash_number']) }}" required>
                    @error('gcash_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="form-text">Shown to customers so they know where to send their 10% reservation fee.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label">GCash Account Name</label>
                    <input type="text" name="gcash_account_name" class="form-control @error('gcash_account_name') is-invalid @enderror" value="{{ old('gcash_account_name', $settings['gcash_account_name']) }}" required>
                    @error('gcash_account_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">GCash QR Code</label>
                    @php($qrPath = public_path('assets/img/'.$settings['gcash_qr_filename']))
                    @if (file_exists($qrPath))
                        <div class="mb-2">
                            <img src="{{ asset('assets/img/'.$settings['gcash_qr_filename']) }}?v={{ filemtime($qrPath) }}" alt="Current GCash QR code" class="img-thumbnail" style="max-width: 180px;">
                        </div>
                    @endif
                    <input type="file" name="gcash_qr" accept="image/*" class="form-control @error('gcash_qr') is-invalid @enderror">
                    @error('gcash_qr')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="form-text">Upload a screenshot of your GCash QR code to replace the one shown to customers. Leave blank to keep the current one.</div>
                </div>
                <hr>

                <div class="mb-3">
                    <label class="form-label">Report Recipients <span class="text-muted">(comma-separated emails, optional)</span></label>
                    <input type="text" name="report_recipients" class="form-control @error('report_recipients') is-invalid @enderror" placeholder="owner@example.com, manager@example.com" value="{{ old('report_recipients', $settings['report_recipients']) }}">
                    @error('report_recipients')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="form-text">The daily and weekly business report is emailed here automatically. Leave blank to send to all admin accounts instead.</div>
                </div>

                <button type="submit" class="btn btn-primary">Save Settings</button>
            </form>
        </div>
    </div>
@endsection
