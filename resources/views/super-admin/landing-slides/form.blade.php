@extends('layouts.dashboard')

@php($editing = $slide->exists)

@section('title', $editing ? 'Edit Foto Beranda' : 'Tambah Foto Beranda')
@section('breadcrumb', 'Karusel Beranda')

@section('content')
<x-ui.page-header eyebrow="Konten publik" :title="$editing ? 'Edit foto kegiatan' : 'Tambah foto kegiatan'" description="Gunakan URL foto kegiatan RKDD. Foto terbaik adalah yang bercerita: suasana belajar, proses berkarya, dan kebersamaan.">
    <x-slot:actions><x-ui.button :href="route('super-admin.landing-slides.index')" variant="ghost" icon="bi-arrow-left">Kembali</x-ui.button></x-slot:actions>
</x-ui.page-header>

<div class="skuad-card p-4 p-lg-5 mx-auto" style="max-width: 62rem">
    <form method="POST" action="{{ $editing ? route('super-admin.landing-slides.update', $slide) : route('super-admin.landing-slides.store') }}">
        @csrf
        @if($editing) @method('PUT') @endif
        <div class="row g-3">
            <div class="col-md-8"><label class="form-label" for="title">Judul</label><input class="form-control @error('title') is-invalid @enderror" id="title" name="title" value="{{ old('title', $slide->title) }}" required>@error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-md-4"><label class="form-label" for="eyebrow">Label kecil</label><input class="form-control @error('eyebrow') is-invalid @enderror" id="eyebrow" name="eyebrow" value="{{ old('eyebrow', $slide->eyebrow) }}" placeholder="Kegiatan RKDD">@error('eyebrow')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-12"><label class="form-label" for="description">Deskripsi</label><textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ old('description', $slide->description) }}</textarea>@error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-12"><label class="form-label" for="image_url">URL foto</label><input class="form-control @error('image_url') is-invalid @enderror" id="image_url" name="image_url" type="url" value="{{ old('image_url', $slide->image_url) }}" placeholder="https://..." required>@error('image_url')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-md-6"><label class="form-label" for="cta_label">Label tombol opsional</label><input class="form-control @error('cta_label') is-invalid @enderror" id="cta_label" name="cta_label" value="{{ old('cta_label', $slide->cta_label) }}" placeholder="Lihat dokumentasi">@error('cta_label')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-md-6"><label class="form-label" for="cta_url">URL tombol opsional</label><input class="form-control @error('cta_url') is-invalid @enderror" id="cta_url" name="cta_url" type="url" value="{{ old('cta_url', $slide->cta_url) }}" placeholder="https://...">@error('cta_url')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-md-4"><label class="form-label" for="display_order">Urutan</label><input class="form-control @error('display_order') is-invalid @enderror" id="display_order" name="display_order" type="number" min="0" max="999" value="{{ old('display_order', $slide->display_order ?? 0) }}" required>@error('display_order')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-md-8 d-flex align-items-end"><input type="hidden" name="is_active" value="0"><div class="form-check form-switch"><input class="form-check-input" id="is_active" name="is_active" type="checkbox" value="1" @checked(old('is_active', $slide->is_active ?? true))><label class="form-check-label" for="is_active">Tampilkan di beranda</label></div></div>
        </div>
        <div class="d-flex justify-content-end gap-2 mt-4"><x-ui.button :href="route('super-admin.landing-slides.index')" variant="ghost">Batal</x-ui.button><x-ui.button type="submit">Simpan</x-ui.button></div>
    </form>
</div>
@endsection
