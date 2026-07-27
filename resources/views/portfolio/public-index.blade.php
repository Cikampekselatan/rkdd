@extends('layouts.app')
@section('title','Showcase Karya RKDD')
@section('content')
<main class="container py-5 portfolio-page">
    <section class="portfolio-public-hero">
        <p>Showcase RKDD</p>
        <h1>Ide muda, proses nyata, karya bermakna.</h1>
        <span>Koleksi karya peserta lintas program yang telah melalui kurasi pembina.</span>
    </section>

    <form class="portfolio-filter" method="GET">
        <select class="form-select" name="program" onchange="this.form.submit()">
            <option value="">Semua program</option>
            @foreach($programs as $program)
                <option value="{{ $program->slug }}" @selected($selectedProgram?->id === $program->id)>{{ $program->name }}</option>
            @endforeach
        </select>
        <button class="btn btn-primary"><i class="bi bi-funnel"></i> Filter</button>
    </form>

    <div class="portfolio-grid">
        @forelse($items as $item)
            <a class="portfolio-card" href="{{ route('portfolio.public.show',$item) }}">
                <div class="portfolio-thumb">
                    @if($item->thumbnail_path)
                        <img src="{{ route('portfolio.assets',[$item,'thumbnail']) }}" alt="Thumbnail {{ $item->title }}">
                    @else
                        <i class="bi bi-palette2"></i>
                    @endif
                    @if($item->is_featured)<b><i class="bi bi-stars"></i> Pilihan</b>@endif
                </div>
                <div class="portfolio-card-copy">
                    <small>{{ $item->workTypeLabel() }} Â· {{ $item->owner->name }} @if($item->programBatch?->program)Â· {{ $item->programBatch->program->name }}@endif</small>
                    <h2>{{ $item->title }}</h2>
                    <p>{{ Str::limit($item->description,110) }}</p>
                </div>
            </a>
        @empty
            <div class="portfolio-empty"><i class="bi bi-stars"></i><h2>Showcase segera hadir</h2><p>Karya yang disetujui untuk publik akan tampil di sini.</p></div>
        @endforelse
    </div>

    {{ $items->links() }}
</main>
@endsection
