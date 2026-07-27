@extends('layouts.dashboard')

@section('title', 'Karusel Beranda RKDD')
@section('breadcrumb', 'Karusel Beranda')

@section('content')
<x-ui.page-header eyebrow="Konten publik" title="Karusel Foto Kegiatan RKDD" description="Kelola foto kegiatan yang tampil di beranda publik RKDD.">
    <x-slot:actions>
        <x-ui.button :href="route('super-admin.knowledge-resources.index')" variant="ghost" icon="bi-book">Ruang Ilmu</x-ui.button>
        <x-ui.button :href="route('super-admin.profile-video.edit')" variant="ghost" icon="bi-play-circle">Video Profil</x-ui.button>
        <x-ui.button :href="route('super-admin.landing-slides.create')" icon="bi-plus-lg">Tambah Foto</x-ui.button>
    </x-slot:actions>
</x-ui.page-header>

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

<div class="skuad-table-wrap">
    <div class="table-responsive">
        <table class="table skuad-table align-middle mb-0">
            <thead><tr><th>Foto</th><th>Judul</th><th>Status</th><th>Urutan</th><th class="text-end">Aksi</th></tr></thead>
            <tbody>
                @forelse($slides as $slide)
                    <tr>
                        <td data-label="Foto"><img src="{{ $slide->image_url }}" alt="{{ $slide->title }}" style="width: 96px; height: 64px; object-fit: cover; border-radius: 18px"></td>
                        <td data-label="Judul"><strong>{{ $slide->title }}</strong><small class="d-block text-secondary">{{ $slide->eyebrow ?: 'Foto kegiatan RKDD' }}</small></td>
                        <td data-label="Status"><x-ui.badge :variant="$slide->is_active ? 'success' : 'secondary'">{{ $slide->is_active ? 'Tampil' : 'Arsip' }}</x-ui.badge></td>
                        <td data-label="Urutan">{{ $slide->display_order }}</td>
                        <td data-label="Aksi" class="text-end">
                            <div class="d-inline-flex gap-2">
                                <a class="skuad-icon-button" href="{{ route('super-admin.landing-slides.edit', $slide) }}"><i class="bi bi-pencil"></i></a>
                                <form method="POST" action="{{ route('super-admin.landing-slides.destroy', $slide) }}" data-confirm="Arsipkan foto ini?">@csrf @method('DELETE')<button class="skuad-icon-button text-danger"><i class="bi bi-archive"></i></button></form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5"><x-ui.empty-state title="Belum ada foto kegiatan" description="Tambahkan URL foto kegiatan RKDD agar beranda terasa hidup." icon="bi-images" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-4">{{ $slides->links() }}</div>
@endsection
