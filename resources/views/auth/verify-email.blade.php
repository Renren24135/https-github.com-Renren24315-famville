@extends('layouts.guest')

@section('title', 'Verify Email')

@section('content')
    <h4 class="mb-3 text-center">Verify Your Email</h4>

    <p class="text-muted small">
        Thanks for signing up! Before getting started, please verify your email address by clicking the link we
        just emailed you. If you didn't receive it, we can send another.
    </p>

    @if (session('status') == 'verification-link-sent')
        <div class="alert alert-success small">A new verification link has been sent to your email address.</div>
    @endif

    <div class="d-flex align-items-center justify-content-between mt-3">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="btn btn-primary">Resend Verification Email</button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn btn-link">Log Out</button>
        </form>
    </div>
@endsection
