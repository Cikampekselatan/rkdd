@extends('layouts.dashboard')
@section('title', ($item->exists ? 'Edit' : 'Tambah').' Portofolio - SKUAD')
@section('breadcrumb', $item->exists ? 'Edit portofolio' : 'Tambah portofolio')
@section('content')
<div class="portfolio-page">
    <section class="portfolio-form-head"><div><p class="skuad-eyebrow">Studio karya</p><h1>{{ $item->exists ? 'Perbarui cerita karya' : 'Tambahkan karya terbaikmu' }}</h1><span>Jelaskan proses dengan jujur. Perubahan pada karya yang dibagikan akan ditinjau ulang pembina.</span></div></section>
    @if($errors->any())<div class="alert alert-danger"><strong>Periksa kembali isian.</strong><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <form class="portfolio-form" method="POST" enctype="multipart/form-data" action="{{ $item->exists ? route('student.portfolio.update', $item) : route('student.portfolio.store') }}">
        @csrf @if($item->exists) @method('PUT') @endif
        <section><div class="portfolio-step">01</div><div><h2>Sumber karya</h2><p>Pilih karya yang sudah dinilai atau proyek mandiri.</p></div></section>
        <div class="row g-3">
            <div class="col-md-4"><label class="form-label" for="source_type">Sumber</label><select class="form-select" id="source_type" name="source_type"><option value="independent" @selected(old('source_type',$item->source_type)==='independent')>Karya mandiri</option><option value="graded" @selected(old('source_type',$item->source_type)==='graded')>Tugas yang dinilai</option></select></div>
            <div class="col-md-8"><label class="form-label" for="submission_id">Tugas bernilai</label><select class="form-select" id="submission_id" name="submission_id"><option value="">Pilih tugas</option>@foreach($eligibleGrades as $grade)<option value="{{ $grade->submission_id }}" @selected((string)old('submission_id',$item->submission_id)===(string)$grade->submission_id)>{{ $grade->submission->assignment->title }} — {{ number_format((float)$grade->total_score,0) }}</option>@endforeach</select><div class="form-text">Wajib hanya jika memilih tugas yang dinilai.</div></div>
        </div>
        <section><div class="portfolio-step">02</div><div><h2>Identitas dan cerita</h2><p>Berikan konteks agar orang memahami nilai karyamu.</p></div></section>
        <div class="row g-3">
            <div class="col-md-8"><label class="form-label" for="title">Judul</label><input class="form-control" id="title" name="title" value="{{ old('title',$item->title) }}" required maxlength="255"></div>
            <div class="col-md-4"><label class="form-label" for="work_type">Jenis karya</label><select class="form-select" id="work_type" name="work_type" required>@foreach($workTypes as $type)<option value="{{ $type->slug }}" @selected(old('work_type',$item->work_type)===$type->slug)>{{ $type->name }}</option>@endforeach</select></div>
            <div class="col-12"><label class="form-label" for="description">Deskripsi</label><textarea class="form-control" id="description" name="description" rows="5" required>{{ old('description',$item->description) }}</textarea></div>
            <div class="col-md-6"><label class="form-label" for="reflection">Refleksi proses</label><textarea class="form-control" id="reflection" name="reflection" rows="5">{{ old('reflection',$item->reflection) }}</textarea></div>
            <div class="col-md-6"><label class="form-label" for="sources">Sumber/referensi</label><textarea class="form-control" id="sources" name="sources" rows="5">{{ old('sources',$item->sources) }}</textarea></div>
        </div>
        <section><div class="portfolio-step">03</div><div><h2>Bukti proses</h2><p>File disimpan privat dan hanya dibuka sesuai izin.</p></div></section>
        <div class="row g-3">
            <div class="col-md-4"><label class="form-label" for="thumbnail">Thumbnail</label><input class="form-control" id="thumbnail" type="file" name="thumbnail" accept="image/jpeg,image/png,image/webp"><div class="form-text">JPG, PNG, WEBP maksimal 2 MB.</div></div>
            <div class="col-md-4"><label class="form-label" for="initial_file">File versi awal</label><input class="form-control" id="initial_file" type="file" name="initial_file"><input class="form-control mt-2" name="initial_url" value="{{ old('initial_url',$item->initial_url) }}" placeholder="atau https://..."></div>
            <div class="col-md-4"><label class="form-label" for="final_file">File versi final</label><input class="form-control" id="final_file" type="file" name="final_file"><input class="form-control mt-2" name="final_url" value="{{ old('final_url',$item->final_url) }}" placeholder="atau https://..."><div class="form-text">Karya mandiri wajib punya file atau URL final.</div></div>
        </div>
        <section><div class="portfolio-step">04</div><div><h2>Transparansi dan akses</h2><p>Deklarasikan bantuan AI dan tentukan siapa yang dapat melihat.</p></div></section>
        <div class="row g-3">
            <div class="col-12"><div class="form-check form-switch"><input class="form-check-input" id="ai_used" type="checkbox" name="ai_used" value="1" @checked(old('ai_used',$item->ai_used))><label class="form-check-label" for="ai_used">Saya menggunakan bantuan AI pada karya ini</label></div></div>
            <div class="col-md-6"><label class="form-label" for="ai_tools">Alat AI</label><input class="form-control" id="ai_tools" name="ai_tools" value="{{ old('ai_tools',$item->ai_tools) }}" placeholder="Contoh: ChatGPT, Canva Magic Design"></div>
            <div class="col-md-6"><label class="form-label" for="ai_usage_description">Cara penggunaan</label><textarea class="form-control" id="ai_usage_description" name="ai_usage_description" rows="3">{{ old('ai_usage_description',$item->ai_usage_description) }}</textarea></div>
            <div class="col-md-6"><label class="form-label" for="visibility">Visibilitas</label><select class="form-select" id="visibility" name="visibility">@foreach($visibilities as $visibility)<option value="{{ $visibility->value }}" @selected(old('visibility',$item->visibility?->value ?? 'private')===$visibility->value)>{{ $visibility->label() }}{{ $visibility->requiresApproval() ? ' — perlu persetujuan' : '' }}</option>@endforeach</select></div>
        </div>
        <div class="portfolio-sticky"><a class="btn btn-light" href="{{ $item->exists ? route('student.portfolio.show',$item) : route('student.portfolio.index') }}">Batal</a><button class="btn btn-primary" type="submit"><i class="bi bi-check2-circle"></i> Simpan portofolio</button></div>
    </form>
</div>
@endsection
