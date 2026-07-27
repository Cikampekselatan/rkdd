@extends('layouts.dashboard')

@php($editing = $resource->exists)

@section('title', $editing ? 'Edit Ruang Ilmu' : 'Tambah Ruang Ilmu')
@section('breadcrumb', 'Ruang Ilmu')

@section('content')
<x-ui.page-header eyebrow="Konten publik" :title="$editing ? 'Edit konten Ruang Ilmu' : 'Tambah konten Ruang Ilmu'" description="Masukkan URL bacaan, eBook, panduan, artikel, atau video tutorial yang bermanfaat.">
    <x-slot:actions><x-ui.button :href="route('super-admin.knowledge-resources.index')" variant="ghost" icon="bi-arrow-left">Kembali</x-ui.button></x-slot:actions>
</x-ui.page-header>

<div class="skuad-card p-4 p-lg-5 mx-auto" style="max-width: 62rem">
    <form method="POST" action="{{ $editing ? route('super-admin.knowledge-resources.update', $resource) : route('super-admin.knowledge-resources.store') }}">
        @csrf
        @if($editing) @method('PUT') @endif
        <div class="row g-3">
            <div class="col-md-8"><label class="form-label" for="title">Judul</label><input class="form-control @error('title') is-invalid @enderror" id="title" name="title" value="{{ old('title', $resource->title) }}" required>@error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-md-4"><label class="form-label" for="slug">Slug</label><input class="form-control @error('slug') is-invalid @enderror" id="slug" name="slug" value="{{ old('slug', $resource->slug) }}" placeholder="otomatis dari judul">@error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-md-4"><label class="form-label" for="content_type">Tipe</label><select class="form-select @error('content_type') is-invalid @enderror" id="content_type" name="content_type" required>@foreach(['ebook' => 'eBook/Bacaan', 'article' => 'Artikel', 'guide' => 'Panduan', 'video' => 'Video tutorial'] as $value => $label)<option value="{{ $value }}" @selected(old('content_type', $resource->content_type ?: 'ebook') === $value)>{{ $label }}</option>@endforeach</select>@error('content_type')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-md-4"><label class="form-label" for="category">Kategori</label><input class="form-control @error('category') is-invalid @enderror" id="category" name="category" value="{{ old('category', $resource->category ?: 'Literasi Digital') }}" required>@error('category')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-md-4"><label class="form-label" for="display_order">Urutan</label><input class="form-control @error('display_order') is-invalid @enderror" id="display_order" name="display_order" type="number" min="0" max="999" value="{{ old('display_order', $resource->display_order ?? 0) }}" required>@error('display_order')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-12"><label class="form-label" for="thumbnail_url">URL thumbnail</label><input class="form-control @error('thumbnail_url') is-invalid @enderror" id="thumbnail_url" name="thumbnail_url" type="url" value="{{ old('thumbnail_url', $resource->thumbnail_url) }}" placeholder="https://..." required>@error('thumbnail_url')<div class="invalid-feedback">{{ $message }}</div>@enderror<div class="form-text">Bisa memakai URL gambar langsung atau link Google Drive yang sudah dibagikan dengan akses Viewer. Jika host menolak gambar, kartu otomatis memakai tampilan pengganti agar tidak rusak.</div></div>
            <div class="col-12"><label class="form-label" for="resource_url">URL konten</label><input class="form-control @error('resource_url') is-invalid @enderror" id="resource_url" name="resource_url" type="url" value="{{ old('resource_url', $resource->resource_url) }}" placeholder="https://..." required>@error('resource_url')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-12"><label class="form-label" for="description">Deskripsi</label><textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="4">{{ old('description', $resource->description) }}</textarea>@error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-md-6"><input type="hidden" name="is_featured" value="0"><div class="form-check form-switch"><input class="form-check-input" id="is_featured" name="is_featured" type="checkbox" value="1" @checked(old('is_featured', $resource->is_featured ?? false))><label class="form-check-label" for="is_featured">Jadikan unggulan</label></div></div>
            <div class="col-md-6"><input type="hidden" name="is_active" value="0"><div class="form-check form-switch"><input class="form-check-input" id="is_active" name="is_active" type="checkbox" value="1" @checked(old('is_active', $resource->is_active ?? true))><label class="form-check-label" for="is_active">Publish ke Ruang Ilmu</label></div></div>
        </div>
        <div class="d-flex justify-content-end gap-2 mt-4"><x-ui.button :href="route('super-admin.knowledge-resources.index')" variant="ghost">Batal</x-ui.button><x-ui.button type="submit">Simpan</x-ui.button></div>
    </form>
</div>
@endsection
