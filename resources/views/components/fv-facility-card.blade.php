@props(['asset', 'type', 'icon'])

{{--
    The venue-status visual unit — a lane or billiard table rendered as a
    "live board" tile: status dot+label, price, and (if a session is
    currently running) a live time readout. Clicking it opens the shared
    availability modal via the existing delegated listener in
    partials/availability-modal.blade.php, which matches on these exact
    data-asset-* attributes — keep them as-is if this component is reused
    elsewhere.
--}}
@php
    $active = $asset->activeBooking();
    $isLane = $type === 'lane';
    // A date-based closure (tournament, maintenance day, etc.) overrides
    // the asset's live status for display purposes when it covers today —
    // "Vacant" would be misleading if nobody can actually book it today.
    $closure = \App\Models\AssetClosure::closing($asset, now()->toDateString());
    $displayColor = $closure ? 'danger' : $asset->status->color();
    $displayLabel = $closure ? 'Closed' : $asset->status->label();
    $displayStatus = $closure ? 'closed' : $asset->status->value;
@endphp

<div {{ $attributes->class(['fv-facility-card', 'fv-status-'.$displayColor, $displayStatus === 'occupied' ? 'fv-facility-card--live' : '']) }}
     role="button" tabindex="0"
     data-asset-type="{{ $type }}" data-asset-id="{{ $asset->id }}" data-asset-name="{{ $asset->name }}" data-asset-status="{{ $displayStatus }}">
    <div class="fv-facility-card-head">
        <span class="fv-facility-card-icon"><i class="bi {{ $icon }}"></i></span>
        <div class="d-flex flex-column align-items-end gap-1">
            <x-fv-status-dot :color="$displayColor" :label="$displayLabel" />
            @if ($displayStatus === 'occupied')
                <span class="fv-facility-card-live-tag" aria-hidden="true"><span class="fv-facility-card-live-dot"></span> IN USE</span>
            @endif
        </div>
    </div>

    <div>
        <h3 class="fv-facility-card-name">{{ $asset->name }}</h3>
        @if ($isLane)
            <div class="fv-facility-card-price fv-mono">&#8369;{{ number_format($asset->price_per_scoresheet, 2) }} / scoresheet</div>
        @endif
    </div>

    @if ($closure)
        <div class="text-muted small mt-1">{{ $closure->reason }}</div>
    @elseif ($active)
        <div>
            @if ($isLane && $active->session_started_at && $active->session_ends_at)
                <span class="badge bg-secondary fv-mono">{{ $active->session_started_at->format('g:i A') }} &ndash; {{ $active->session_ends_at->format('g:i A') }}</span>
            @elseif ($isLane && $active->session_started_at)
                <span class="badge bg-secondary">Since {{ $active->session_started_at->format('g:i A') }}</span>
                <div class="text-muted small mt-1">playtime varies per customer</div>
            @elseif (! $isLane && $active->session_ends_at)
                <span class="badge asset-timer" data-countdown="{{ $active->session_ends_at->toIso8601String() }}" data-session-start="{{ $active->session_started_at?->toIso8601String() }}">--:--</span>
                <div class="text-muted small mt-1">time remaining</div>
            @elseif (! $isLane && $active->session_started_at)
                <span class="badge bg-secondary">Since {{ $active->session_started_at->format('g:i A') }}</span>
                <div class="text-muted small mt-1">billed hourly at checkout</div>
            @endif
        </div>
    @endif

    <div class="fv-facility-card-foot"><i class="bi bi-calendar-week"></i> View timetable</div>
</div>
