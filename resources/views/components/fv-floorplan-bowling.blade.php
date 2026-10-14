@props(['lanes'])

{{--
    Each Lane row IS a pair unit in this venue's actual data — a single
    booking/price/status covers two physical lanes (named e.g. "Lane 1
    & 2"), not two separate rows. So one row = one pair block here,
    split into a left/right visual lane (numbers parsed from the name
    when it follows that "N & M" pattern, falling back to a single
    wide tile for any lane named differently). Both sides share the
    same data-asset-* attributes since they're the same booking unit.
--}}
<div class="fv-floorplan-frame fv-floorplan-frame--bowling">
    <div class="fv-floorplan-compass"><i class="bi bi-compass"></i> N</div>

    <div class="fv-floorplan-grid-bowling">
        @forelse ($lanes as $index => $lane)
            @php
                $isPairNamed = (bool) preg_match('/(\d+)\D+(\d+)/', $lane->name, $m);
                $sideLabels = $isPairNamed ? ['Lane '.$m[1], 'Lane '.$m[2]] : [$lane->name];
            @endphp
            <div class="fv-floorplan-pair">
                <div class="fv-floorplan-pair-label">PAIR {{ chr(65 + $index) }}</div>
                <div class="fv-floorplan-lanes">
                    @php($closure = \App\Models\AssetClosure::closing($lane, now()->toDateString()))
                    @foreach ($sideLabels as $sideLabel)
                        <div class="fv-floorplan-lane {{ $isPairNamed ? '' : 'fv-floorplan-lane--single' }} fv-status-{{ $closure ? 'danger' : $lane->status->color() }}"
                             role="button" tabindex="0"
                             data-asset-type="lane" data-asset-id="{{ $lane->id }}" data-asset-name="{{ $lane->name }}" data-asset-status="{{ $closure ? 'closed' : $lane->status->value }}">
                            <div class="fv-floorplan-lane-pindeck">
                                <div class="fv-floorplan-pins">
                                    <div class="fv-floorplan-pin-row"><span class="fv-floorplan-pin"></span><span class="fv-floorplan-pin"></span><span class="fv-floorplan-pin"></span><span class="fv-floorplan-pin"></span></div>
                                    <div class="fv-floorplan-pin-row"><span class="fv-floorplan-pin"></span><span class="fv-floorplan-pin"></span><span class="fv-floorplan-pin"></span></div>
                                    <div class="fv-floorplan-pin-row"><span class="fv-floorplan-pin"></span><span class="fv-floorplan-pin"></span></div>
                                    <div class="fv-floorplan-pin-row"><span class="fv-floorplan-pin"></span></div>
                                </div>
                            </div>
                            <div class="fv-floorplan-lane-body">
                                @if (! $closure && $lane->status->value === 'occupied')
                                    <span class="fv-floorplan-ball" aria-hidden="true"></span>
                                @endif
                                <span class="fv-floorplan-lane-status"><x-fv-status-dot :color="$closure ? 'danger' : $lane->status->color()" :label="$closure ? 'Closed' : $lane->status->label()" /></span>
                                <span class="fv-floorplan-lane-arrows">
                                    <span></span><span></span><span></span><span></span><span></span>
                                </span>
                                <span class="fv-floorplan-lane-label">{{ $sideLabel }}</span>
                                <span class="fv-floorplan-lane-foul"></span>
                            </div>
                            <div class="fv-floorplan-lane-approach">APPROACH</div>
                        </div>
                    @endforeach
                </div>
            </div>
        @empty
            <p class="text-muted small mb-0">No bowling lanes configured yet.</p>
        @endforelse
    </div>

    <div class="fv-floorplan-entry"><i class="bi bi-door-open"></i> ENTRY</div>
</div>
