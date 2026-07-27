@props([
    'href' => null,
    'variant' => 'primary',
    'icon' => null,
    'type' => 'button',
])

@php
    $classes = match ($variant) {
        'secondary' => 'btn btn-skuad-secondary',
        'outline' => 'btn btn-skuad-outline',
        'danger' => 'btn btn-skuad-danger',
        'ghost' => 'btn btn-skuad-ghost',
        default => 'btn btn-skuad-primary',
    };
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class([$classes, 'd-inline-flex align-items-center justify-content-center gap-2']) }}>
        @if ($icon)
            <i class="bi {{ $icon }}" aria-hidden="true"></i>
        @endif
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->class([$classes, 'd-inline-flex align-items-center justify-content-center gap-2']) }}>
        @if ($icon)
            <i class="bi {{ $icon }}" aria-hidden="true"></i>
        @endif
        {{ $slot }}
    </button>
@endif
