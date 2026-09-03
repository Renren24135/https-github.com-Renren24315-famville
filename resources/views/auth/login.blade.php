@extends('layouts.guest')

@section('title', 'Log In')

@section('content')
    <h4 class="mb-3 text-center">Sign In</h4>

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                   class="form-control @error('email') is-invalid @enderror">
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input id="password" type="password" name="password" required autocomplete="current-password"
                   class="form-control @error('password') is-invalid @enderror">
            @error('password')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3 form-check">
            <input type="checkbox" class="form-check-input" name="remember" id="remember_me">
            <label class="form-check-label" for="remember_me">Remember me</label>
        </div>

        <div class="d-flex align-items-center justify-content-between">
            @if (Route::has('password.request'))
                <a class="small text-decoration-none" href="{{ route('password.request') }}">Forgot your password?</a>
            @endif

            <button type="submit" class="btn btn-primary ms-auto">Log In</button>
        </div>
    </form>

    <hr>
    <p class="text-center small mb-0">Don't have an account? <a href="{{ route('register') }}">Register here</a></p>
@endsection
