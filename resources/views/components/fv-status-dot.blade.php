@props(['color' => 'secondary', 'label'])

{{--
    Status indicator — a colored dot paired with its text label, always.
    Never color-only (accessibility rule from the design brief). `color`
    matches the Bootstrap color keywords already returned by every status
    enum in this app (success/warning/danger/secondary/info), so callers
    just pass `$model->status->color()` straight through.
--}}
<span {{ $attributes->class(['fv-status', 'fv-status-'.$color]) }}>
    <span class="fv-status-indicator"></span>{{ $label }}
</span>
