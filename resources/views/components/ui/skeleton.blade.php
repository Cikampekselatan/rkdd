@props(['lines' => 3, 'avatar' => false])

<div {{ $attributes->class(['skuad-skeleton-wrap']) }} aria-label="Memuat konten" aria-busy="true">
    @if ($avatar)
        <span class="skuad-skeleton skuad-skeleton-avatar"></span>
    @endif
    <div class="flex-grow-1">
        @for ($line = 1; $line <= $lines; $line++)
            <span class="skuad-skeleton skuad-skeleton-line" style="--line-width: {{ $line === $lines ? '62%' : '100%' }}"></span>
        @endfor
    </div>
</div>
