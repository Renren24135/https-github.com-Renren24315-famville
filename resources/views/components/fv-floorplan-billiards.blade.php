@props(['tables'])

{{--
    Architectural billiards layout — tables flow in a responsive grid
    (not absolute pixel coordinates) so it reflows at any width, and
    carry the same data-asset-* contract as fv-facility-card so the
    existing delegated click listener in partials/availability-modal
    picks them up with zero new JS. Each tile is styled as an actual
    pool table (wood rail + 6 pockets + felt), not a flat rectangle.
--}}
<div class="fv-floorplan-frame fv-floorplan-frame--billiards">
    <div class="fv-floorplan-compass"><i class="bi bi-compass"></i> N</div>

    <div class="fv-floorplan-grid-billiards">
        @forelse ($tables as $table)
            @php($closure = \App\Models\AssetClosure::closing($table, now()->toDateString()))
            <div class="fv-floorplan-table fv-status-{{ $closure ? 'danger' : $table->status->color() }}"
                 role="button" tabindex="0"
                 data-asset-type="table" data-asset-id="{{ $table->id }}" data-asset-name="{{ $table->name }}" data-asset-status="{{ $closure ? 'closed' : $table->status->value }}">
                <span class="fv-floorplan-pocket fv-floorplan-pocket-tl"></span>
                <span class="fv-floorplan-pocket fv-floorplan-pocket-tr"></span>
                <span class="fv-floorplan-pocket fv-floorplan-pocket-ml"></span>
                <span class="fv-floorplan-pocket fv-floorplan-pocket-mr"></span>
                <span class="fv-floorplan-pocket fv-floorplan-pocket-bl"></span>
                <span class="fv-floorplan-pocket fv-floorplan-pocket-br"></span>
                <div class="fv-floorplan-table-felt">
                    <span class="fv-floorplan-table-status"><x-fv-status-dot :color="$closure ? 'danger' : $table->status->color()" :label="$closure ? 'Closed' : $table->status->label()" /></span>
                    @if ($closure)
                        {{-- No balls — the table isn't in play, it's closed. --}}
                    @elseif ($table->status->value === 'occupied')
                        <div class="fv-floorplan-balls fv-floorplan-balls--playing" aria-hidden="true">
                            <span class="fv-ball fv-ball--cue"></span>
                            <span class="fv-ball fv-ball--c3"></span>
                            <span class="fv-ball fv-ball--c2"></span>
                            <span class="fv-ball fv-ball--c1"></span>
                        </div>
                    @else
                        <div class="fv-floorplan-balls fv-floorplan-balls--rack" aria-hidden="true">
                            <div class="fv-floorplan-ball-row"><span class="fv-ball fv-ball--c3"></span><span class="fv-ball fv-ball--c2"></span><span class="fv-ball fv-ball--c1"></span></div>
                            <div class="fv-floorplan-ball-row"><span class="fv-ball fv-ball--c8"></span><span class="fv-ball fv-ball--c5"></span></div>
                            <div class="fv-floorplan-ball-row"><span class="fv-ball fv-ball--c6"></span></div>
                        </div>
                    @endif
                    <span class="fv-floorplan-table-label">{{ $table->name }}</span>
                </div>
            </div>
        @empty
            <p class="text-muted small mb-0">No billiard tables configured yet.</p>
        @endforelse
    </div>

    <div class="fv-floorplan-entry"><i class="bi bi-door-open"></i> ENTRY</div>
</div>
