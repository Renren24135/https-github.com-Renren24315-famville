@extends('layouts.app')

@section('title', 'Book a Slot')

@section('content')
    <h3 class="mb-4"><i class="bi bi-plus-circle"></i> Book a Lane or Table</h3>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('customer.bookings.store') }}">
                @csrf

                <label class="form-label d-block">What would you like to book?</label>
                <ul class="nav nav-pills mb-3" id="assetTypeTabs">
                    <li class="nav-item">
                        <button class="nav-link active" type="button" data-bs-toggle="pill" data-bs-target="#laneTab" onclick="document.getElementById('asset_type').value='lane'">
                            <i class="bi bi-align-center"></i> Bowling Lane
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" type="button" data-bs-toggle="pill" data-bs-target="#tableTab" onclick="document.getElementById('asset_type').value='table'">
                            <i class="bi bi-circle"></i> Billiard Table
                        </button>
                    </li>
                </ul>
                <input type="hidden" name="asset_type" id="asset_type" value="lane">

                <div class="tab-content mb-3">
                    <div class="tab-pane fade show active" id="laneTab">
                        <div class="row g-2">
                            @foreach ($lanes as $lane)
                                <div class="col-6 col-md-4">
                                    <input type="radio" class="btn-check" name="lane_radio" id="lane_{{ $lane->id }}"
                                           value="{{ $lane->id }}" onchange="document.getElementById('asset_id').value=this.value"
                                           {{ $lane->status->value !== 'available' ? 'disabled' : '' }} required>
                                    <label class="btn btn-outline-primary w-100" for="lane_{{ $lane->id }}">
                                        {{ $lane->name }}<br>
                                        <small>&#8369;{{ number_format($lane->price_per_scoresheet, 2) }}/scoresheet</small><br>
                                        <span class="badge bg-{{ $lane->status->color() }}">{{ $lane->status->label() }}</span>
                                    </label>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-3">
                            <label for="scoresheets" class="form-label">Number of Scoresheets</label>
                            <input type="number" min="1" max="20" name="scoresheets" id="scoresheets" class="form-control" style="max-width:200px" value="{{ old('scoresheets', 1) }}">
                        </div>
                    </div>

                    <div class="tab-pane fade" id="tableTab">
                        <div class="row g-2">
                            @foreach ($tables as $table)
                                <div class="col-6 col-md-4">
                                    <input type="radio" class="btn-check" name="table_radio" id="table_{{ $table->id }}"
                                           value="{{ $table->id }}" onchange="document.getElementById('asset_id').value=this.value"
                                           {{ $table->status->value !== 'available' ? 'disabled' : '' }}>
                                    <label class="btn btn-outline-primary w-100" for="table_{{ $table->id }}">
                                        {{ $table->name }}<br>
                                        <span class="badge bg-{{ $table->status->color() }}">{{ $table->status->label() }}</span>
                                    </label>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-3">
                            <label for="hours" class="form-label">Number of Hours</label>
                            <input type="number" min="1" max="8" name="hours" id="hours" class="form-control" style="max-width:200px" value="{{ old('hours', 1) }}">
                        </div>
                    </div>
                </div>

                <input type="hidden" id="asset_id" name="asset_id" value="{{ old('asset_id') }}">

                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="booking_date" class="form-label">Date</label>
                        <input type="date" name="booking_date" id="booking_date" class="form-control @error('booking_date') is-invalid @enderror" min="{{ now()->toDateString() }}" value="{{ old('booking_date', now()->toDateString()) }}" required>
                        @error('booking_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label for="start_time" class="form-label">Start Time</label>
                        <input type="time" name="start_time" id="start_time" class="form-control @error('start_time') is-invalid @enderror" value="{{ old('start_time') }}" required>
                        @error('start_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> Submit Booking Request</button>
                </div>
                <p class="text-muted small mt-2">Your booking will be reviewed and approved by our cashier. Payment is made at the counter.</p>
            </form>
        </div>
    </div>
@endsection
