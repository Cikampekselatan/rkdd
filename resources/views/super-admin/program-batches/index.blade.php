@extends('layouts.dashboard')
@section('title', 'Batch/Periode Program RKDD')
@section('breadcrumb', 'Batch Program')
@section('content')
<x-ui.page-header eyebrow="Master platform" title="Batch/Periode Program" description="Satu program dapat dijalankan di beberapa sekolah/lembaga dan periode berbeda tanpa data bercampur.">
    <x-slot:actions><div class="d-flex flex-wrap gap-2"><x-ui.button :href="route('super-admin.programs.index')" variant="ghost" icon="bi-diagram-3">Program</x-ui.button><x-ui.button :href="route('super-admin.institutions.index')" variant="ghost" icon="bi-buildings">Lembaga</x-ui.button><x-ui.button :href="route('super-admin.program-batches.create')" icon="bi-plus-lg">Tambah batch</x-ui.button></div></x-slot:actions>
</x-ui.page-header>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
<div class="skuad-table-wrap"><div class="table-responsive"><table class="table skuad-table">
    <thead><tr><th>Batch/Periode</th><th>Program</th><th>Lembaga</th><th>Peruntukan</th><th>Sebutan</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
    <tbody>
        @forelse($batches as $batch)
            <tr>
                <td><strong>{{ $batch->name }}</strong><small class="d-block text-muted">{{ $batch->period_label }}</small></td>
                <td>{{ $batch->program->name }}</td>
                <td>{{ $batch->institution->name }}</td>
                <td>{{ str($batch->audience_type)->headline() }}</td>
                <td>{{ $batch->participant_label }}</td>
                <td><x-ui.badge :variant="$batch->is_active ? 'success' : 'neutral'">{{ $batch->is_active ? 'Aktif' : 'Arsip' }}</x-ui.badge></td>
                <td class="text-end"><div class="d-inline-flex gap-1"><a class="skuad-icon-button" href="{{ route('super-admin.program-batches.edit', $batch) }}" aria-label="Edit {{ $batch->name }}"><i class="bi bi-pencil"></i></a><form method="POST" action="{{ route('super-admin.program-batches.destroy', $batch) }}" data-confirm="Hapus batch/periode ini?">@csrf @method('DELETE')<button class="skuad-icon-button text-danger" aria-label="Hapus {{ $batch->name }}"><i class="bi bi-trash"></i></button></form></div></td>
            </tr>
        @empty
            <tr><td colspan="7"><x-ui.empty-state title="Belum ada batch/periode" description="Buat batch agar program dapat dijalankan di lembaga tertentu." icon="bi-calendar-range" /></td></tr>
        @endforelse
    </tbody>
</table></div></div>
<div class="mt-4">{{ $batches->links() }}</div>
@endsection
