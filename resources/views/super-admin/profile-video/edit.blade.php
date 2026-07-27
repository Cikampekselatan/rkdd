@extends('layouts.dashboard')

@section('title', 'Video Profil RKDD')
@section('breadcrumb', 'Video Profil')

@section('content')
<x-ui.page-header eyebrow="Konten publik" title="Video Profil RKDD" description="Simpan URL video profil yang tampil di beranda publik.">
    <x-slot:actions>
        <x-ui.button :href="route('super-admin.knowledge-resources.index')" variant="ghost" icon="bi-book">Ruang Ilmu</x-ui.button>
        <x-ui.button :href="route('super-admin.landing-slides.index')" variant="ghost" icon="bi-images">Karusel</x-ui.button>
    </x-slot:actions>
</x-ui.page-header>

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

<div class="skuad-card p-4 p-lg-5 mx-auto" style="max-width: 62rem">
    <form method="POST" action="{{ route('super-admin.profile-video.update') }}">
        @csrf
        @method('PUT')
        <div class="row g-3">
            <div class="col-12"><label class="form-label" for="title">Judul</label><input class="form-control @error('title') is-invalid @enderror" id="title" name="title" value="{{ old('title', $video->title) }}" required>@error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-12"><label class="form-label" for="description">Deskripsi</label><textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="4">{{ old('description', $video->description) }}</textarea>@error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-12"><label class="form-label" for="video_url">URL video</label><input class="form-control @error('video_url') is-invalid @enderror" id="video_url" name="video_url" type="url" value="{{ old('video_url', $video->video_url) }}" placeholder="https://youtu.be/..." required>@error('video_url')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-12"><label class="form-label" for="thumbnail_url">URL thumbnail opsional</label><input class="form-control @error('thumbnail_url') is-invalid @enderror" id="thumbnail_url" name="thumbnail_url" type="url" value="{{ old('thumbnail_url', $video->thumbnail_url) }}" placeholder="https://...">@error('thumbnail_url')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-12"><input type="hidden" name="is_active" value="0"><div class="form-check form-switch"><input class="form-check-input" id="is_active" name="is_active" type="checkbox" value="1" @checked(old('is_active', $video->is_active ?? true))><label class="form-check-label" for="is_active">Tampilkan video profil di beranda</label></div></div>
        </div>
        <div class="d-flex justify-content-end mt-4"><x-ui.button type="submit">Simpan video profil</x-ui.button></div>
    </form>
</div>
@endsection
