@extends('layouts.guest')

@section('title', 'Forgot Password')

@section('content')
    <h4 class="mb-3 text-center">Forgot Password</h4>

    <p class="text-muted small">Enter your email address and we will send you a password reset link.</p>

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                   class="form-control @error('email') is-invalid @enderror">
            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="d-flex justify-content-end">
            <button type="submit" class="btn btn-primary">Email Password Reset Link</button>
        </div>
    </form>
@endsection
