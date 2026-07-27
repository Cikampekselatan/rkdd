@extends('layouts.dashboard')

@php($editing = $highlight->exists)

@section('title', $editing ? 'Edit Hasil Terbaik' : 'Tambah Hasil Terbaik')
@section('breadcrumb', $editing ? 'Edit Hasil Terbaik' : 'Tambah Hasil Terbaik')

@section('content')
<x-ui.page-header eyebrow="Showcase publik" :title="$editing ? 'Edit hasil terbaik' : 'Tambah hasil terbaik'" description="Masukkan URL karya pilihan. Sistem akan menampilkan foto, video, audio, dokumen, atau tautan sesuai jenis URL.">
    <x-slot:actions>
        <x-ui.button :href="route('showcase-highlights.index')" variant="ghost" icon="bi-arrow-left">Kembali</x-ui.button>
    </x-slot:actions>
</x-ui.page-header>

<div class="skuad-card p-4 p-lg-5 mx-auto" style="max-width: 62rem">
    <form method="POST" action="{{ $editing ? route('showcase-highlights.update', $highlight) : route('showcase-highlights.store') }}">
        @csrf
        @if($editing) @method('PUT') @endif

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="period">Periode tampil</label>
                <select class="form-select @error('period') is-invalid @enderror" id="period" name="period" required>
                    @foreach($periods as $period)
                        <option value="{{ $period->value }}" @selected(old('period', $highlight->period?->value ?? 'weekly') === $period->value)>{{ $period->label() }}</option>
                    @endforeach
                </select>
                @error('period')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label" for="display_order">Urutan tampil</label>
                <input class="form-control @error('display_order') is-invalid @enderror" id="display_order" name="display_order" type="number" min="0" max="999" value="{{ old('display_order', $highlight->display_order ?? 0) }}">
                @error('display_order')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-7">
                <label class="form-label" for="title">Judul karya</label>
                <input class="form-control @error('title') is-invalid @enderror" id="title" name="title" value="{{ old('title', $highlight->title) }}" required>
                @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-5">
                <label class="form-label" for="student_name">Nama siswa/kelompok</label>
                <input class="form-control @error('student_name') is-invalid @enderror" id="student_name" name="student_name" value="{{ old('student_name', $highlight->student_name) }}">
                @error('student_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-12">
                <label class="form-label" for="url">URL hasil terbaik</label>
                <input class="form-control @error('url') is-invalid @enderror" id="url" name="url" type="url" value="{{ old('url', $highlight->url) }}" placeholder="https://..." required>
                <div class="form-text">Bisa berupa URL gambar, video, YouTube, Google Drive, audio, dokumen, atau tautan karya lain.</div>
                @error('url')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label" for="media_type">Jenis media</label>
                <select class="form-select @error('media_type') is-invalid @enderror" id="media_type" name="media_type">
                    <option value="">Deteksi otomatis dari URL</option>
                    @foreach($mediaTypes as $type)
                        <option value="{{ $type->value }}" @selected(old('media_type', $highlight->media_type?->value) === $type->value)>{{ $type->label() }}</option>
                    @endforeach
                </select>
                @error('media_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6 d-flex align-items-end">
                <div>
                    <input type="hidden" name="is_active" value="0">
                    <div class="form-check form-switch">
                        <input class="form-check-input" id="is_active" name="is_active" type="checkbox" value="1" @checked(old('is_active', $highlight->is_active ?? true))>
                        <label class="form-check-label" for="is_active">Tampilkan di dashboard publik</label>
                    </div>
                </div>
            </div>
            <div class="col-12">
                <label class="form-label" for="caption">Catatan motivasi</label>
                <textarea class="form-control @error('caption') is-invalid @enderror" id="caption" name="caption" rows="4">{{ old('caption', $highlight->caption) }}</textarea>
                @error('caption')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="d-flex flex-wrap justify-content-end gap-2 mt-4">
            <x-ui.button :href="route('showcase-highlights.index')" variant="ghost">Batal</x-ui.button>
            <x-ui.button type="submit">{{ $editing ? 'Simpan perubahan' : 'Terbitkan hasil terbaik' }}</x-ui.button>
        </div>
    </form>
</div>
@endsection
