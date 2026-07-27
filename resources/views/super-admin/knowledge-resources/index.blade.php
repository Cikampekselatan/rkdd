@extends('layouts.dashboard')

@section('title', 'Ruang Ilmu')
@section('breadcrumb', 'Ruang Ilmu')

@section('content')
<x-ui.page-header eyebrow="Konten publik" title="Ruang Ilmu RKDD" description="Kelola bacaan, eBook, artikel, panduan, dan video tutorial yang tampil untuk publik.">
    <x-slot:actions>
        <x-ui.button :href="route('super-admin.landing-slides.index')" variant="ghost" icon="bi-images">Karusel</x-ui.button>
        <x-ui.button :href="route('super-admin.profile-video.edit')" variant="ghost" icon="bi-play-circle">Video Profil</x-ui.button>
        <x-ui.button :href="route('super-admin.knowledge-resources.create')" icon="bi-plus-lg">Tambah Konten</x-ui.button>
    </x-slot:actions>
</x-ui.page-header>

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

<form class="portfolio-filter mb-3" method="GET">
    <input class="form-control" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Cari judul, kategori, atau deskripsi">
    <select class="form-select" name="content_type">
        <option value="">Semua tipe</option>
        @foreach(['ebook' => 'eBook/Bacaan', 'article' => 'Artikel', 'guide' => 'Panduan', 'video' => 'Video tutorial'] as $value => $label)
            <option value="{{ $value }}" @selected(($filters['content_type'] ?? '') === $value)>{{ $label }}</option>
        @endforeach
    </select>
    <button class="btn btn-primary"><i class="bi bi-funnel"></i> Filter</button>
</form>

<div class="skuad-table-wrap">
    <div class="table-responsive">
        <table class="table skuad-table align-middle mb-0">
            <thead><tr><th>Konten</th><th>Tipe</th><th>Kategori</th><th>Status</th><th>Urutan</th><th class="text-end">Aksi</th></tr></thead>
            <tbody>
                @forelse($resources as $resource)
                    <tr>
                        <td data-label="Konten"><strong>{{ $resource->title }}</strong><small class="d-block text-secondary">{{ $resource->is_featured ? 'Unggulan' : 'Reguler' }}</small></td>
                        <td data-label="Tipe">{{ $resource->typeLabel() }}</td>
                        <td data-label="Kategori">{{ $resource->category }}</td>
                        <td data-label="Status"><x-ui.badge :variant="$resource->is_active ? 'success' : 'secondary'">{{ $resource->is_active ? 'Publish' : 'Arsip' }}</x-ui.badge></td>
                        <td data-label="Urutan">{{ $resource->display_order }}</td>
                        <td data-label="Aksi" class="text-end"><div class="d-inline-flex gap-2"><a class="skuad-icon-button" href="{{ route('super-admin.knowledge-resources.edit', $resource) }}"><i class="bi bi-pencil"></i></a><form method="POST" action="{{ route('super-admin.knowledge-resources.destroy', $resource) }}" data-confirm="Arsipkan konten ini?">@csrf @method('DELETE')<button class="skuad-icon-button text-danger"><i class="bi bi-archive"></i></button></form></div></td>
                    </tr>
                @empty
                    <tr><td colspan="6"><x-ui.empty-state title="Ruang Ilmu masih kosong" description="Tambahkan bacaan atau video tutorial dari URL." icon="bi-book" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-4">{{ $resources->links() }}</div>
@endsection
