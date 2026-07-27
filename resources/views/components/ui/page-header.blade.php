@props(['eyebrow' => null, 'title', 'description' => null])

<div {{ $attributes->class(['skuad-page-header']) }}>
    <div>
        @if ($eyebrow)
            <p class="skuad-eyebrow">{{ $eyebrow }}</p>
        @endif
        <h1>{{ $title }}</h1>
        @if ($description)
            <p>{{ $description }}</p>
        @endif
    </div>
    @if (isset($actions))
        <div class="skuad-page-actions">{{ $actions }}</div>
    @endif
</div>
