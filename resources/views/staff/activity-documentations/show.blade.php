@extends('layouts.dashboard')

@section('title', $documentation->title.' - Dokumentasi')
@section('breadcrumb', 'Detail dokumentasi')

@section('content')
<div class="phase12-page">
    <div class="phase12-detail-hero">
        <div>
            <p>{{ $documentation->academicYear->name }} · {{ $documentation->creator->roles->first()?->slug->label() }}</p>
            <h1>{{ $documentation->title }}</h1>
            <span>{{ $documentation->activity_date->translatedFormat('l, d F Y') }} · dibuat oleh {{ $documentation->creator->name }}</span>
        </div>
        <strong class="phase12-status status-verified">Dokumentasi</strong>
    </div>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

    <div class="phase12-detail-grid">
        <main class="phase12-paper">
            <section>
                <small>Deskripsi kegiatan</small>
                <p>{!! nl2br(e($documentation->description ?: 'Belum ada deskripsi.')) !!}</p>
            </section>

            @if($documentation->photo_path)
                <section>
                    <small>Foto kegiatan</small>
                    <p><img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($documentation->photo_path) }}" alt="Foto {{ $documentation->title }}" style="width:100%;max-height:480px;object-fit:cover;border-radius:18px"></p>
                    <p class="text-secondary small mb-0">{{ $documentation->photo_original_name }}</p>
                </section>
            @endif

            <div class="d-flex flex-wrap gap-2">
                @if($documentation->resource_url)<a class="btn btn-outline-primary" href="{{ $documentation->resource_url }}" target="_blank" rel="noopener"><i class="bi bi-link-45deg"></i> Buka URL dokumentasi</a>@endif
                @if($documentation->video_url)<a class="btn btn-outline-primary" href="{{ $documentation->video_url }}" target="_blank" rel="noopener"><i class="bi bi-play-circle"></i> Buka video</a>@endif
                @can('update', $documentation)<a class="btn btn-primary" href="{{ route('activity-documentations.edit', $documentation) }}">Edit</a>@endcan
            </div>
        </main>
        <aside class="phase12-side">
            <h2>Akses laporan</h2>
            <p class="text-secondary">Dokumentasi ini dapat dilihat oleh Guru/Pembina, Instruktur/Coach, Admin, Super Admin, dan Kepala Sekolah.</p>
            @can('delete', $documentation)
                <form method="POST" action="{{ route('activity-documentations.destroy', $documentation) }}" onsubmit="return confirm('Hapus dokumentasi ini?')">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-outline-danger w-100" type="submit">Hapus dokumentasi</button>
                </form>
            @endcan
        </aside>
    </div>
</div>
@endsection
