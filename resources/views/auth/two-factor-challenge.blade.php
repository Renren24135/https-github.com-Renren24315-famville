@extends('layouts.guest')

@section('title', 'Two-Factor Verification')

@section('content')
    <h4 class="mb-3 text-center"><i class="bi bi-shield-lock"></i> Two-Factor Verification</h4>

    <div id="codeStep">
        <p class="text-muted small text-center">Enter the 6-digit code from your authenticator app.</p>

        <form method="POST" action="{{ route('two-factor.verify') }}">
            @csrf

            <div class="mb-3">
                <label for="code" class="form-label">Authentication Code</label>
                <input id="code" type="text" name="code" inputmode="numeric" autocomplete="one-time-code" autofocus required
                       class="form-control form-control-lg text-center @error('code') is-invalid @enderror" maxlength="6" placeholder="123456">
                @error('code')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <button type="submit" class="btn btn-primary w-100">Verify</button>
        </form>

        <p class="text-center small mt-3 mb-0">
            <a href="#" id="showRecoveryStep">Use a recovery code instead</a>
        </p>
    </div>

    <div id="recoveryStep" class="d-none">
        <p class="text-muted small text-center">Enter one of the recovery codes you saved when you set up two-factor authentication.</p>

        <form method="POST" action="{{ route('two-factor.recovery') }}">
            @csrf

            <div class="mb-3">
                <label for="recovery_code" class="form-label">Recovery Code</label>
                <input id="recovery_code" type="text" name="recovery_code" autocomplete="off" required
                       class="form-control form-control-lg text-center text-uppercase @error('recovery_code') is-invalid @enderror" placeholder="XXXXX-XXXXX">
                @error('recovery_code')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <button type="submit" class="btn btn-primary w-100">Verify</button>
        </form>

        <p class="text-center small mt-3 mb-0">
            <a href="#" id="showCodeStep">Use your authenticator app instead</a>
        </p>
    </div>

    <hr>
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="btn btn-link btn-sm w-100 text-decoration-none">Sign out</button>
    </form>

    <script nonce="{{ $cspNonce }}">
        document.getElementById('showRecoveryStep').addEventListener('click', function (e) {
            e.preventDefault();
            document.getElementById('codeStep').classList.add('d-none');
            document.getElementById('recoveryStep').classList.remove('d-none');
        });
        document.getElementById('showCodeStep').addEventListener('click', function (e) {
            e.preventDefault();
            document.getElementById('recoveryStep').classList.add('d-none');
            document.getElementById('codeStep').classList.remove('d-none');
        });
    </script>
@endsection
