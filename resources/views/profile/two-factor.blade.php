@extends('layouts.app')

@section('title', 'Two-Factor Authentication')

@section('content')
    <h3 class="mb-4"><i class="bi bi-shield-lock"></i> Two-Factor Authentication</h3>

    @if (session('recoveryCodes'))
        <div class="alert alert-warning" style="max-width: 600px;">
            <strong><i class="bi bi-exclamation-triangle"></i> Save these recovery codes now.</strong>
            <p class="small mb-2">Each one works once, if you ever lose access to your authenticator app. They won't be shown again.</p>
            <div class="bg-white border rounded p-2 font-monospace small">
                @foreach (session('recoveryCodes') as $code)
                    {{ $code }}<br>
                @endforeach
            </div>
        </div>
    @endif

    <div class="card shadow-sm" style="max-width: 600px;">
        <div class="card-body">
            @if ($enabled)
                <p class="mb-3"><span class="badge bg-success">Enabled</span> Two-factor authentication is protecting this account.</p>

                <form method="POST" action="{{ route('two-factor.recovery-codes') }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-outline-secondary btn-sm">Generate New Recovery Codes</button>
                </form>

                <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#disable2faModal">Disable Two-Factor</button>
            @else
                <p class="text-muted small mb-3">Scan this QR code with an authenticator app (Google Authenticator, Authy, etc.), then enter the 6-digit code it shows to turn this on.</p>

                <div class="text-center mb-3">
                    <img src="{{ $qrCode }}" alt="Two-factor authentication QR code" style="max-width: 240px;">
                </div>

                <p class="small text-muted text-center">Can't scan it? Enter this key manually:</p>
                <p class="text-center font-monospace small mb-3">{{ $secret }}</p>

                <form method="POST" action="{{ route('two-factor.enable') }}" style="max-width: 300px;" class="mx-auto">
                    @csrf
                    <label for="code" class="form-label">Authentication Code</label>
                    <input id="code" type="text" name="code" inputmode="numeric" autocomplete="one-time-code" required
                           class="form-control form-control-lg text-center mb-2 @error('code') is-invalid @enderror" maxlength="6" placeholder="123456">
                    @error('code')
                        <div class="invalid-feedback d-block mb-2">{{ $message }}</div>
                    @enderror
                    <button type="submit" class="btn btn-primary w-100">Enable Two-Factor</button>
                </form>
            @endif
        </div>
    </div>

    @if ($enabled)
        <div class="modal fade" id="disable2faModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="{{ route('two-factor.disable') }}">
                        @csrf
                        @method('DELETE')
                        <div class="modal-header">
                            <h5 class="modal-title">Disable Two-Factor Authentication</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p>This removes the extra verification step from your login. Anyone with just your password would be able to sign in.</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-danger">Disable</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endsection
