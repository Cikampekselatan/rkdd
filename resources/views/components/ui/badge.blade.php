@props(['variant' => 'neutral', 'icon' => null])

<span {{ $attributes->class(['skuad-badge', "skuad-badge-{$variant}"]) }}>
    @if ($icon)
        <i class="bi {{ $icon }}" aria-hidden="true"></i>
    @endif
    {{ $slot }}
</span>
