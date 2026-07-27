@extends('layouts.dashboard')

@section('title', 'Detail Absen Pengajar')
@section('breadcrumb', 'Detail absen')

@section('content')
@php($creatorRole = $log->teacher->roles->first()?->slug?->label() ?? 'Pengajar')
<div class="phase12-page">
    <div class="phase12-detail-hero">
        <div>
            <p>ABSEN PENGAJAR · NO. {{ str_pad($log->log_number, 3, '0', STR_PAD_LEFT) }}</p>
            <h1>{{ $log->material }}</h1>
            <span>{{ $log->activity_date->translatedFormat('l, d F Y') }} · {{ $log->teacher->name }} · {{ $creatorRole }}</span>
        </div>
        <strong class="phase12-status status-{{ $log->status->value }}">{{ $log->status->label() }}</strong>
    </div>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    <div class="phase12-detail-grid">
        <main class="phase12-paper">
            <section><small>Materi</small><p>{{ $log->material }}</p></section>
            <section><small>Kegiatan</small><p>{!! nl2br(e($log->activities)) !!}</p></section>
            <section><small>Penugasan</small><p>{{ $log->assignment ?: 'Tidak ada penugasan.' }}</p></section>
            <div class="phase12-initials">
                <div><strong>Tanda tangan {{ $creatorRole }}</strong><span>{{ $log->signature_path ? $log->teacher->name : 'Belum ditandatangani' }}</span></div>
                <div><strong>Tanda tangan verifikator</strong><span>{{ $log->reviewer_signature_path ? ($log->verifier?->name ?? 'Sudah ditandatangani') : 'Belum ditandatangani Guru/Pembina' }}</span></div>
            </div>
            @if($log->rejection_note)<div class="alert alert-danger"><strong>Catatan penolakan</strong><br>{{ $log->rejection_note }}</div>@endif
            <div class="d-flex flex-wrap gap-2">
                @can('update',$log)<a class="btn btn-primary" href="{{ route('activity-logs.edit',$log) }}">Edit / ajukan ulang</a>@endcan
                <a class="btn btn-outline-primary" href="{{ route('activity-logs.print',$log) }}" target="_blank"><i class="bi bi-printer"></i> Tampilan A4</a>
                @if($log->signature_path)<a class="btn btn-outline-secondary" href="{{ route('activity-logs.signature',$log) }}"><i class="bi bi-lock"></i> Tanda tangan pengajar</a>@endif
                @if($log->reviewer_signature_path)<a class="btn btn-outline-secondary" href="{{ route('activity-logs.signature',[$log,'kind'=>'reviewer']) }}"><i class="bi bi-lock"></i> Tanda tangan verifikator</a>@endif
            </div>
        </main>
        <aside class="phase12-side">
            @can('review',$log)
                <form method="POST" enctype="multipart/form-data" action="{{ route('activity-logs.review',$log) }}" class="phase12-review phase12-signature-upload" data-signature-pad>
                    @csrf
                    @method('PATCH')
                    <h2>Verifikasi {{ auth()->user()->hasRole(\App\Enums\RoleSlug::Teacher) ? 'Guru/Pembina' : 'Instruktur/Coach' }}</h2>
                    <textarea class="form-control" name="rejection_note" rows="3" placeholder="Wajib jika ditolak"></textarea>
                    @if(auth()->user()->hasRole(\App\Enums\RoleSlug::Teacher))
                        <small>Tanda tangan Guru/Pembina wajib diisi saat menyetujui absen dari Instruktur/Coach.</small>
                        <div class="phase12-signature-choice" role="radiogroup" aria-label="Metode tanda tangan verifikator">
                            <label><input type="radio" name="reviewer_signature_method" value="draw" checked data-signature-method><span><i class="bi bi-vector-pen"></i> Tanda tangan langsung</span></label>
                            <label><input type="radio" name="reviewer_signature_method" value="upload" data-signature-method><span><i class="bi bi-upload"></i> Unggah file</span></label>
                        </div>
                        <div class="phase12-signature-draw" data-signature-draw-panel>
                            <canvas data-signature-canvas aria-label="Area tanda tangan verifikator"></canvas>
                            <input type="hidden" name="reviewer_signature_drawn" value="{{ old('reviewer_signature_drawn') }}" data-signature-output>
                            <div class="phase12-signature-tools">
                                <button class="btn btn-outline-secondary btn-sm" type="button" data-signature-clear><i class="bi bi-eraser"></i> Bersihkan</button>
                                <small>Gunakan jari/stylus di ponsel/tablet atau mouse di PC.</small>
                            </div>
                        </div>
                        <div class="phase12-signature-file d-none" data-signature-upload-panel>
                            <input class="form-control" type="file" name="reviewer_signature" accept="image/png,image/jpeg,image/webp" data-signature-file>
                        </div>
                    @endif
                    <div><button class="btn btn-outline-danger" name="decision" value="rejected">Tolak</button><button class="btn btn-success" name="decision" value="verified">Verifikasi & teruskan</button></div>
                </form>
            @endcan
            <h2>Audit</h2>
            <div class="phase12-audit">@foreach($log->audits->sortByDesc('created_at') as $audit)<div><strong>{{ $audit->actor?->name ?? 'Sistem' }}</strong><span>{{ ucfirst(str_replace('_',' ',$audit->event)) }}</span><small>{{ $audit->created_at->translatedFormat('d M Y H:i') }}</small></div>@endforeach</div>
        </aside>
    </div>
</div>
@endsection
