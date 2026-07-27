@extends('layouts.dashboard')

@section('title', 'Materi dan Bacaan Peserta')
@section('breadcrumb', 'Dokumen')

@section('content')
    <div class="document-center-page">
        <x-ui.page-header
            eyebrow="Pustaka belajar peserta"
            title="Materi dan Bacaan Peserta"
            description="Ruang ini hanya berisi bahan pembelajaran, bacaan, panduan, asesmen, dan alat belajar peserta. Dokumen staff seperti RPP, kurikulum internal, silabus, dan administrasi tidak ditampilkan di sini."
        />

        <form class="student-document-filter" method="GET">
            <input class="form-control" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Cari materi, bacaan, atau panduan">
            <select class="form-select" name="category">
                <option value="">Semua bahan peserta</option>
                @foreach($categories as $category)
                    <option value="{{ $category->value }}" @selected(($filters['category'] ?? '') === $category->value)>{{ $category->label() }}</option>
                @endforeach
            </select>
            <x-ui.button type="submit" icon="bi-search">Cari</x-ui.button>
        </form>

        <div class="student-document-grid">
            @forelse($resources as $resource)
                <article class="student-document-card">
                    <div class="document-card-icon"><i class="bi {{ $resource->category->icon() }}"></i></div>
                    <div class="d-flex flex-wrap gap-2">
                        <x-ui.badge>{{ $resource->category->label() }}</x-ui.badge>
                        @if($resource->is_pinned)
                            <x-ui.badge variant="warning" icon="bi-pin-angle-fill">Pilihan</x-ui.badge>
                        @endif
                    </div>
                    <h2>{{ $resource->title }}</h2>
                    <p>{{ $resource->description ?: 'Bahan pendukung pembelajaran dan bacaan peserta program.' }}</p>
                    <small>{{ $resource->academicYear?->name ?? 'Semua tahun' }}{{ $resource->semester ? ' · Semester '.$resource->semester : '' }}</small>
                    <div class="student-document-actions">
                        <x-ui.button type="button" variant="outline" data-document-preview data-preview-url="{{ $resource->preview_url }}" data-preview-title="{{ $resource->title }}" data-document-url="{{ $resource->drive_url }}" icon="bi-eye">Preview</x-ui.button>
                        <x-ui.button :href="route('student.documents.show', $resource)" icon="bi-arrow-up-right">Buka</x-ui.button>
                    </div>
                </article>
            @empty
                <div class="skuad-card grid-column-full">
                    <x-ui.empty-state title="Belum ada bahan belajar peserta" description="Materi, bacaan, atau panduan yang dipublikasikan pembina akan muncul di sini." icon="bi-folder2-open" />
                </div>
            @endforelse
        </div>

        <div>{{ $resources->links() }}</div>
    </div>

    @include('documents._preview-modal')
@endsection
