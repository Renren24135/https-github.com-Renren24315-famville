@props(['lanes', 'tables', 'mode' => 'guest'])

{{--
    VenueMap — the interactive floor plan. Facility switcher + live
    legend + zoom controls wrap the two spatial panels (bowling/
    billiards). Every tile inside the panels carries the same
    data-asset-type/id/name/status attributes as fv-facility-card, so
    the existing global click listener (partials/availability-modal)
    opens the shared inspector modal with zero new wiring — this
    component only adds the switcher/zoom interactivity for itself.
    `mode` (guest|customer|staff) is informational here (legend/copy
    only); the inspector modal's own action footer reads it from
    whichever @include('partials.availability-modal', ['mode' => ...])
    the host page passed in.
--}}
@php
    $fvFpMerged = $lanes->concat($tables);
    // A closure overrides an asset's live status for display everywhere
    // else on the floor plan (see fv-floorplan-bowling/billiards) — the
    // legend has to agree, or "closed" tiles would still silently count
    // as "available" here.
    $fvFpEffectiveStatus = fn ($asset) => \App\Models\AssetClosure::closing($asset, now()->toDateString())
        ? 'closed'
        : $asset->status->value;
    $fvFpCounts = collect(['available', 'reserved', 'occupied', 'maintenance', 'closed'])->mapWithKeys(
        fn ($status) => [$status => $fvFpMerged->filter(fn ($a) => $fvFpEffectiveStatus($a) === $status)->count()]
    );
@endphp

<div {{ $attributes->class(['fv-floorplan']) }}>
    <div class="fv-floorplan-toolbar">
        <div class="fv-floorplan-switcher" role="group" aria-label="Facility switcher">
            <button type="button" class="fv-floorplan-switch-btn is-active" data-floorplan-panel="bowling">
                <i class="bi bi-align-center"></i> Bowling
            </button>
            <button type="button" class="fv-floorplan-switch-btn" data-floorplan-panel="billiards">
                <i class="bi bi-circle"></i> Billiards
            </button>
        </div>

        <div class="fv-floorplan-legend">
            <x-fv-status-dot color="success" :label="$fvFpCounts['available'].' Available'" />
            <x-fv-status-dot color="warning" :label="$fvFpCounts['reserved'].' Reserved'" />
            <x-fv-status-dot color="danger" :label="$fvFpCounts['occupied'].' Occupied'" />
            <x-fv-status-dot color="secondary" :label="$fvFpCounts['maintenance'].' Maintenance'" />
            @if ($fvFpCounts['closed'] > 0)
                <x-fv-status-dot color="danger" :label="$fvFpCounts['closed'].' Closed'" />
            @endif
        </div>

        <div class="fv-floorplan-zoom">
            <button type="button" class="fv-floorplan-zoom-btn" data-floorplan-zoom="out" aria-label="Zoom out"><i class="bi bi-dash-lg"></i></button>
            <button type="button" class="fv-floorplan-zoom-btn" data-floorplan-zoom="reset" aria-label="Reset zoom"><i class="bi bi-aspect-ratio"></i></button>
            <button type="button" class="fv-floorplan-zoom-btn" data-floorplan-zoom="in" aria-label="Zoom in"><i class="bi bi-plus-lg"></i></button>
        </div>
    </div>

    <div class="fv-floorplan-viewport">
        <div class="fv-floorplan-stage">
            <div class="fv-floorplan-panel is-active" data-floorplan-panel-content="bowling">
                <x-fv-floorplan-bowling :lanes="$lanes" />
            </div>
            <div class="fv-floorplan-panel" data-floorplan-panel-content="billiards">
                <x-fv-floorplan-billiards :tables="$tables" />
            </div>
        </div>
    </div>

    <p class="text-muted small mt-2 mb-0"><i class="bi bi-info-circle"></i> Bowling: {{ $lanes->count() }} lanes &middot; Billiards: {{ $tables->count() }} tables &middot; {{ $fvFpMerged->count() }} assets total. Click any lane or table for its live status and timetable.</p>
</div>

<script nonce="{{ $cspNonce }}">
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.fv-floorplan').forEach(function (root) {
            var switchBtns = root.querySelectorAll('[data-floorplan-panel]');
            switchBtns.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var target = btn.dataset.floorplanPanel;
                    switchBtns.forEach(function (b) { b.classList.toggle('is-active', b === btn); });
                    root.querySelectorAll('[data-floorplan-panel-content]').forEach(function (panel) {
                        panel.classList.toggle('is-active', panel.dataset.floorplanPanelContent === target);
                    });
                });
            });

            var stage = root.querySelector('.fv-floorplan-stage');
            var zoom = 1;
            root.querySelectorAll('[data-floorplan-zoom]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var action = btn.dataset.floorplanZoom;
                    if (action === 'in') {
                        zoom = Math.min(1.6, Math.round((zoom + 0.15) * 100) / 100);
                    } else if (action === 'out') {
                        zoom = Math.max(0.7, Math.round((zoom - 0.15) * 100) / 100);
                    } else {
                        zoom = 1;
                    }
                    stage.style.transform = 'scale(' + zoom + ')';
                });
            });
        });
    });
</script>
