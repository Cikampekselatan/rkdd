@extends('layouts.dashboard')

@section('title', ($documentation->exists ? 'Edit' : 'Tambah').' Dokumentasi Kegiatan')
@section('breadcrumb', 'Form dokumentasi')

@section('content')
<div class="phase12-page">
    <x-ui.page-header eyebrow="Dokumentasi kegiatan" :title="$documentation->exists ? 'Perbarui dokumentasi' : 'Tambah dokumentasi kegiatan'" description="Foto akan otomatis dikompresi menjadi JPG di bawah 500 KB. Video disimpan sebagai URL saja." />

    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    <form class="phase12-form" method="POST" enctype="multipart/form-data" action="{{ $documentation->exists ? route('activity-documentations.update', $documentation) : route('activity-documentations.store') }}">
        @csrf
        @if($documentation->exists)@method('PUT')@endif

        <div class="row g-4">
            <div class="col-md-6">
                <label class="form-label">Tahun ajaran</label>
                <select class="form-select" name="academic_year_id" required>
                    @foreach($academicYears as $year)<option value="{{ $year->id }}" @selected(old('academic_year_id', $documentation->academic_year_id) == $year->id)>{{ $year->name }}</option>@endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Tanggal kegiatan</label>
                <input class="form-control" type="date" name="activity_date" max="{{ today()->toDateString() }}" value="{{ old('activity_date', $documentation->activity_date?->toDateString() ?? today()->toDateString()) }}" required>
            </div>
            <div class="col-12">
                <label class="form-label">Judul kegiatan</label>
                <input class="form-control" name="title" value="{{ old('title', $documentation->title) }}" maxlength="255" required>
            </div>
            <div class="col-12">
                <label class="form-label">Deskripsi / catatan dokumentasi</label>
                <textarea class="form-control" name="description" rows="5">{{ old('description', $documentation->description) }}</textarea>
            </div>
            <div class="col-md-4">
                <label class="form-label">Upload foto</label>
                <input class="form-control" type="file" name="photo" accept="image/png,image/jpeg,image/webp">
                <small class="text-secondary">Opsional. Akan dikompresi menjadi JPG &lt; 500 KB.</small>
                @if($documentation->photo_path)<small class="d-block text-success mt-1">Foto lama tersimpan. Upload baru jika ingin mengganti.</small>@endif
            </div>
            <div class="col-md-4">
                <label class="form-label">URL dokumentasi</label>
                <input class="form-control" type="url" name="resource_url" value="{{ old('resource_url', $documentation->resource_url) }}" placeholder="https://drive.google.com/...">
                <small class="text-secondary">Untuk album, folder Drive, atau link pendukung.</small>
            </div>
            <div class="col-md-4">
                <label class="form-label">URL video</label>
                <input class="form-control" type="url" name="video_url" value="{{ old('video_url', $documentation->video_url) }}" placeholder="https://youtube.com/...">
                <small class="text-secondary">Video hanya memakai tautan, bukan upload file.</small>
            </div>
        </div>

        <div class="phase12-sticky">
            <x-ui.button type="submit">Simpan dokumentasi</x-ui.button>
        </div>
    </form>
</div>
@endsection
