@extends('layouts.dashboard')

@section('title', 'Showcase Karya')
@section('breadcrumb', 'Showcase Karya')

@section('content')
<x-ui.page-header eyebrow="Showcase publik" title="Showcase Karya" description="Tempel URL karya pilihan minggu ini atau bulan ini agar tampil di beranda RKDD.">
    <x-slot:actions>
        <x-ui.button :href="route('showcase-highlights.create')" icon="bi-stars">Tambah hasil terbaik</x-ui.button>
    </x-slot:actions>
</x-ui.page-header>

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

<form class="portfolio-filter mb-3" method="GET">
    <input class="form-control" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Cari judul atau nama siswa">
    <select class="form-select" name="period">
        <option value="">Semua periode</option>
        @foreach($periods as $period)
            <option value="{{ $period->value }}" @selected(($filters['period'] ?? '') === $period->value)>{{ $period->label() }}</option>
        @endforeach
    </select>
    <button class="btn btn-primary"><i class="bi bi-funnel" aria-hidden="true"></i> Filter</button>
</form>

<div class="skuad-table-wrap">
    <div class="table-responsive">
        <table class="table skuad-table align-middle mb-0">
            <thead><tr><th>Judul</th><th>Periode</th><th>Media</th><th>Status</th><th>Urutan</th><th class="text-end">Aksi</th></tr></thead>
            <tbody>
                @forelse($highlights as $highlight)
                    <tr>
                        <td data-label="Judul"><strong>{{ $highlight->title }}</strong><small class="d-block text-secondary">{{ $highlight->student_name ?: 'Tanpa nama siswa' }}</small></td>
                        <td data-label="Periode">{{ $highlight->period->label() }}</td>
                        <td data-label="Media">{{ $highlight->media_type->label() }}</td>
                        <td data-label="Status"><x-ui.badge :variant="$highlight->is_active ? 'success' : 'secondary'">{{ $highlight->is_active ? 'Tampil' : 'Nonaktif' }}</x-ui.badge></td>
                        <td data-label="Urutan">{{ $highlight->display_order }}</td>
                        <td data-label="Aksi" class="text-end">
                            <div class="d-inline-flex gap-2">
                                <a class="skuad-icon-button" href="{{ route('showcase-highlights.edit', $highlight) }}" aria-label="Edit {{ $highlight->title }}"><i class="bi bi-pencil" aria-hidden="true"></i></a>
                                <form method="POST" action="{{ route('showcase-highlights.destroy', $highlight) }}" data-confirm="Arsipkan hasil terbaik ini?">@csrf @method('DELETE')<button class="skuad-icon-button text-danger" aria-label="Arsipkan {{ $highlight->title }}"><i class="bi bi-archive" aria-hidden="true"></i></button></form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6"><x-ui.empty-state title="Belum ada hasil terbaik" description="Tambahkan URL karya pilihan untuk dashboard publik." icon="bi-stars" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-4">{{ $highlights->links() }}</div>
@endsection
