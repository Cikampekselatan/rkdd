@props([
    'title',
    'description',
    'icon' => 'bi-inbox',
])

<div {{ $attributes->class(['skuad-empty-state']) }}>
    <span class="skuad-empty-icon" aria-hidden="true"><i class="bi {{ $icon }}"></i></span>
    <h3>{{ $title }}</h3>
    <p>{{ $description }}</p>
    @if (isset($action))
        <div class="mt-3">{{ $action }}</div>
    @endif
</div>
