@php($embedUrl = $highlight->youtubeEmbedUrl())
<article class="public-highlight-card">
    <div class="public-highlight-media">
        @if($highlight->media_type === \App\Enums\ShowcaseMediaType::Image)
            <img src="{{ $highlight->url }}" alt="{{ $highlight->title }}" loading="lazy">
        @elseif($embedUrl)
            <iframe src="{{ $embedUrl }}" title="{{ $highlight->title }}" loading="lazy" allowfullscreen></iframe>
        @elseif($highlight->media_type === \App\Enums\ShowcaseMediaType::Video)
            <video controls preload="metadata" src="{{ $highlight->url }}"></video>
        @elseif($highlight->media_type === \App\Enums\ShowcaseMediaType::Audio)
            <div class="public-audio-preview"><i class="bi bi-soundwave" aria-hidden="true"></i><audio controls src="{{ $highlight->url }}"></audio></div>
        @else
            <div class="public-link-preview"><i class="bi bi-box-arrow-up-right" aria-hidden="true"></i><span>{{ $highlight->media_type->label() }}</span></div>
        @endif
    </div>
    <div class="public-highlight-copy">
        <small>{{ $highlight->period->label() }} · {{ $highlight->media_type->label() }}</small>
        <h3>{{ $highlight->title }}</h3>
        @if($highlight->student_name)<strong>{{ $highlight->student_name }}</strong>@endif
        <p>{{ $highlight->caption ?: 'Karya pilihan pembina SKUAD.' }}</p>
        <a href="{{ $highlight->url }}" target="_blank" rel="noopener">Buka karya <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
    </div>
</article>
