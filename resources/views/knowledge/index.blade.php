@extends('layouts.app')

@section('title', 'Ruang Ilmu RKDD')

@section('content')
<div class="landing-page public-dashboard rkdd-knowledge-page">
    <section class="landing-hero public-hero rkdd-inner-hero">
        <div class="container">
            <p class="landing-kicker"><span></span> Ruang Ilmu</p>
            <h1>Bacaan dan video yang membuat belajar digital terasa dekat.</h1>
            <p class="landing-lead">Kumpulan eBook, artikel, panduan, dan video tutorial pilihan dari RKDD Cikampek Selatan.</p>
            <form class="rkdd-public-filter" method="GET">
                <input class="form-control" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Cari materi">
                <select class="form-select" name="type">
                    <option value="">Semua tipe</option>
                    @foreach(['ebook' => 'eBook/Bacaan', 'article' => 'Artikel', 'guide' => 'Panduan', 'video' => 'Video tutorial'] as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['type'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <button class="btn btn-skuad"><i class="bi bi-search"></i> Cari</button>
            </form>
        </div>
    </section>

    <section class="public-highlights">
        <div class="container">
            <div class="rkdd-knowledge-grid">
                @forelse($resources as $resource)
                    @include('knowledge._card', ['resource' => $resource])
                @empty
                    <article class="public-highlight-empty"><i class="bi bi-book"></i><h3>Materi belum tersedia.</h3><p>Super Admin dapat mengisi Ruang Ilmu dari dashboard.</p></article>
                @endforelse
            </div>
            <div class="mt-4">{{ $resources->links() }}</div>
        </div>
    </section>
</div>
@endsection
