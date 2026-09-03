@extends('layouts.guest')

@section('title', 'Confirm Password')

@section('content')
    <h4 class="mb-3 text-center">Confirm Password</h4>

    <p class="text-muted small">This is a secure area. Please confirm your password before continuing.</p>

    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf

        <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input id="password" type="password" name="password" required autocomplete="current-password"
                   class="form-control @error('password') is-invalid @enderror">
            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="d-flex justify-content-end">
            <button type="submit" class="btn btn-primary">Confirm</button>
        </div>
    </form>
@endsection
