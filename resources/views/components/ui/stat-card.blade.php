@props([
    'label',
    'value',
    'icon' => 'bi-bar-chart',
    'tone' => 'teal',
    'trend' => null,
    'trendDirection' => 'up',
])

<article {{ $attributes->class(['skuad-card skuad-stat-card']) }}>
    <div class="skuad-stat-icon skuad-stat-icon-{{ $tone }}">
        <i class="bi {{ $icon }}" aria-hidden="true"></i>
    </div>
    <div class="min-w-0">
        <p class="skuad-stat-label">{{ $label }}</p>
        <p class="skuad-stat-value">{{ $value }}</p>
        @if ($trend)
            <p class="skuad-stat-trend skuad-stat-trend-{{ $trendDirection }} mb-0">
                <i class="bi {{ $trendDirection === 'down' ? 'bi-arrow-down-right' : 'bi-arrow-up-right' }}" aria-hidden="true"></i>
                {{ $trend }}
            </p>
        @endif
    </div>
</article>
