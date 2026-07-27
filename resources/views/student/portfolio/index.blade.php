@extends('layouts.dashboard')
@section('title', 'Portofolio Saya - SKUAD')
@section('breadcrumb', 'Portofolio saya')
@section('content')
<div class="portfolio-page">
    <section class="portfolio-hero">
        <div><p>Etalase proses belajar</p><h1>Karya yang tumbuh bersama saya.</h1><span>Simpan versi awal, hasil akhir, refleksi, dan cerita di balik setiap karya.</span></div>
        <a class="btn btn-warning btn-lg" href="{{ route('student.portfolio.create') }}"><i class="bi bi-plus-lg"></i> Tambah karya</a>
    </section>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    <div class="portfolio-grid">
        @forelse($items as $item)
            <a class="portfolio-card" href="{{ route('student.portfolio.show', $item) }}">
                <div class="portfolio-thumb">
                    @if($item->thumbnail_path)<img src="{{ route('portfolio.assets', [$item, 'thumbnail']) }}" alt="Thumbnail {{ $item->title }}">@else<i class="bi bi-{{ str_contains($item->work_type, 'video') ? 'camera-reels' : 'palette2' }}"></i>@endif
                    @if($item->is_featured)<b><i class="bi bi-stars"></i> Featured</b>@endif
                </div>
                <div class="portfolio-card-copy"><small>{{ $item->workTypeLabel() }} · {{ $item->source_type === 'graded' ? 'Karya dinilai' : 'Karya mandiri' }}</small><h2>{{ $item->title }}</h2><p>{{ Str::limit($item->description, 110) }}</p><div><span>{{ $item->visibility->label() }}</span><strong class="approval-{{ $item->approval_status->value }}">{{ $item->approval_status->label() }}</strong></div></div>
            </a>
        @empty
            <div class="portfolio-empty"><i class="bi bi-images"></i><h2>Etalase karya masih kosong</h2><p>Mulai dari tugas yang sudah dinilai atau unggah proyek mandirimu.</p><a class="btn btn-primary" href="{{ route('student.portfolio.create') }}">Tambahkan karya pertama</a></div>
        @endforelse
    </div>
    {{ $items->links() }}
</div>
@endsection
