@extends('layouts.dashboard')

@section('title', ($log->exists ? 'Edit' : 'Buat').' Absen Pengajar')
@section('breadcrumb', 'Form absen pengajar')

@section('content')
    <div class="phase12-page">
        <x-ui.page-header
            eyebrow="Form kegiatan"
            :title="$log->exists ? 'Perbarui absen pengajar' : 'Buat absen pengajar'"
            description="Simpan sebagai draf atau ajukan setelah tanda tangan tersedia."
        />

        @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

        <form class="phase12-form" method="POST" enctype="multipart/form-data" action="{{ $log->exists ? route('activity-logs.update', $log) : route('activity-logs.store') }}">
            @csrf
            @if($log->exists)@method('PUT')@endif

            <div class="row g-4">
                <div class="col-md-6">
                    <label class="form-label">Tahun ajaran</label>
                    <select class="form-select" name="academic_year_id" required>
                        @foreach($academicYears as $year)
                            <option value="{{ $year->id }}" @selected(old('academic_year_id', $log->academic_year_id) == $year->id)>{{ $year->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Tanggal</label>
                    <input class="form-control" type="date" name="activity_date" max="{{ today()->toDateString() }}" value="{{ old('activity_date', $log->activity_date?->toDateString() ?? today()->toDateString()) }}" required>
                </div>
                <div class="col-12">
                    <label class="form-label">Materi</label>
                    <textarea class="form-control" name="material" rows="2" required>{{ old('material', $log->material) }}</textarea>
                </div>
                <div class="col-12">
                    <label class="form-label">Kegiatan</label>
                    <textarea class="form-control" name="activities" rows="6" required>{{ old('activities', $log->activities) }}</textarea>
                </div>
                <div class="col-12">
                    <label class="form-label">Penugasan</label>
                    <textarea class="form-control" name="assignment" rows="3">{{ old('assignment', $log->assignment) }}</textarea>
                </div>

                <div class="col-12 phase12-signature-upload" data-signature-pad>
                    <div class="phase12-signature-heading">
                        <div>
                            <label class="form-label mb-1">Tanda tangan digital {{ $log->signature_path ? '(opsional jika ingin mengganti)' : '' }}</label>
                            <small>Pilih salah satu: tanda tangan langsung atau unggah file PNG/JPG/WebP maksimal 2 MB.</small>
                        </div>
                    </div>

                    <div class="phase12-signature-choice" role="radiogroup" aria-label="Metode tanda tangan">
                        <label>
                            <input type="radio" name="signature_method" value="draw" checked data-signature-method>
                            <span><i class="bi bi-vector-pen"></i> Tanda tangan langsung</span>
                        </label>
                        <label>
                            <input type="radio" name="signature_method" value="upload" data-signature-method>
                            <span><i class="bi bi-upload"></i> Unggah file</span>
                        </label>
                    </div>

                    <div class="phase12-signature-draw" data-signature-draw-panel>
                        <canvas data-signature-canvas aria-label="Area tanda tangan langsung"></canvas>
                        <input type="hidden" name="signature_drawn" value="{{ old('signature_drawn') }}" data-signature-output>
                        <div class="phase12-signature-tools">
                            <button class="btn btn-outline-secondary btn-sm" type="button" data-signature-clear><i class="bi bi-eraser"></i> Bersihkan</button>
                            <small>Gunakan jari/stylus di ponsel atau mouse di PC. Tanda tangan tersimpan saat formulir dikirim.</small>
                        </div>
                    </div>

                    <div class="phase12-signature-file d-none" data-signature-upload-panel>
                        <input class="form-control" type="file" name="signature" accept="image/png,image/jpeg,image/webp" data-signature-file>
                        <small>File disimpan privat dan hanya bisa diakses lewat otorisasi.</small>
                    </div>
                </div>
            </div>

            <div class="phase12-sticky">
                <button class="btn btn-outline-primary" name="submit_now" value="0" type="submit">Simpan draf</button>
                <button class="btn btn-primary" name="submit_now" value="1" type="submit"><i class="bi bi-send"></i> Ajukan verifikasi</button>
            </div>
        </form>
    </div>
@endsection
