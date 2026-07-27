<article class="rkdd-knowledge-card">
    @php($thumbnailUrl = $resource->displayThumbnailUrl())
    <div class="rkdd-knowledge-thumb">
        @if($thumbnailUrl)
            <img src="{{ $thumbnailUrl }}" alt="{{ $resource->title }}" loading="lazy" onerror="this.hidden=true; this.nextElementSibling.hidden=false;">
        @endif
        <div class="rkdd-knowledge-thumb-fallback" @if($thumbnailUrl) hidden @endif>
            <i class="bi {{ $resource->typeIcon() }}"></i>
            <strong>{{ $resource->title }}</strong>
            <small>{{ $resource->typeLabel() }}</small>
        </div>
        <span><i class="bi {{ $resource->typeIcon() }}"></i> {{ $resource->typeLabel() }}</span>
    </div>
    <div class="rkdd-knowledge-copy">
        <small>{{ $resource->category }}</small>
        <h3>{{ $resource->title }}</h3>
        <p>{{ $resource->description ?: 'Materi pilihan RKDD untuk memperkaya wawasan digital.' }}</p>
        <a href="{{ $resource->resource_url }}" target="_blank" rel="noopener">Buka materi <i class="bi bi-arrow-up-right"></i></a>
    </div>
</article>
