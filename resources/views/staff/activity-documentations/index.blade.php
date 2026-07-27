@extends('layouts.dashboard')

@section('title', 'Dokumentasi Kegiatan - SKUAD Learning Hub')
@section('breadcrumb', 'Dokumentasi kegiatan')

@section('content')
<div class="phase12-page">
    <x-ui.page-header eyebrow="Laporan dokumentasi" title="Dokumentasi kegiatan ekstrakurikuler" description="Simpan bukti kegiatan berupa URL, foto terkompresi di bawah 500 KB, dan tautan video.">
        <x-slot:actions>
            @can('create', \App\Models\ActivityDocumentation::class)
                <x-ui.button :href="route('activity-documentations.create')" icon="bi-plus-lg">Tambah dokumentasi</x-ui.button>
            @endcan
        </x-slot:actions>
    </x-ui.page-header>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

    <form class="phase12-filter" method="GET">
        <select class="form-select" name="academic_year_id">
            <option value="">Semua tahun</option>
            @foreach($academicYears as $year)<option value="{{ $year->id }}" @selected(($filters['academic_year_id'] ?? '') == $year->id)>{{ $year->name }}</option>@endforeach
        </select>
        <input class="form-control" type="date" name="from" value="{{ $filters['from'] ?? '' }}" aria-label="Dari tanggal">
        <input class="form-control" type="date" name="to" value="{{ $filters['to'] ?? '' }}" aria-label="Sampai tanggal">
        <input class="form-control" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Cari judul/deskripsi">
        <button class="btn btn-outline-primary" type="submit">Filter</button>
    </form>

    <div class="phase12-list">
        @forelse($docs as $doc)
            <a class="phase12-row" href="{{ route('activity-documentations.show', $doc) }}">
                <span class="phase12-number"><i class="bi bi-camera"></i></span>
                <div>
                    <small>{{ $doc->activity_date->translatedFormat('d F Y') }} · {{ $doc->creator->name }} · {{ $doc->creator->roles->first()?->slug->label() }}</small>
                    <h2>{{ $doc->title }}</h2>
                    <p>{{ \Illuminate\Support\Str::limit($doc->description ?: 'Dokumentasi kegiatan SKUAD.', 120) }}</p>
                </div>
                <span class="phase12-status status-verified">{{ collect([$doc->photo_path ? 'Foto' : null, $doc->resource_url ? 'URL' : null, $doc->video_url ? 'Video' : null])->filter()->implode(' · ') }}</span>
                <i class="bi bi-chevron-right"></i>
            </a>
        @empty
            <x-ui.empty-state title="Belum ada dokumentasi" description="Dokumentasi kegiatan dari instruktur/coach atau guru/pembina akan tampil di sini." icon="bi-camera" />
        @endforelse
    </div>
    {{ $docs->links() }}
</div>
@endsection
