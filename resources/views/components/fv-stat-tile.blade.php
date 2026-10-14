@props(['label', 'value', 'sub' => null, 'accent' => false])

<div {{ $attributes->class(['fv-stat-tile', 'fv-stat-tile--accent' => $accent]) }}>
    <div class="fv-stat-tile-label">{{ $label }}</div>
    <div class="fv-stat-tile-value">{{ $value }}</div>
    @if ($sub)
        <div class="fv-stat-tile-sub">{{ $sub }}</div>
    @endif
</div>
