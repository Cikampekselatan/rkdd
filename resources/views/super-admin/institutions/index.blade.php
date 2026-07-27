@extends('layouts.dashboard')
@section('title', 'Lembaga/Penyelenggara RKDD')
@section('breadcrumb', 'Lembaga')
@section('content')
<x-ui.page-header eyebrow="Master platform" title="Lembaga/Penyelenggara" description="Kelola sekolah, RKDD, komunitas, organisasi, atau mitra tempat program berjalan.">
    <x-slot:actions><div class="d-flex flex-wrap gap-2"><x-ui.button :href="route('super-admin.programs.index')" variant="ghost" icon="bi-diagram-3">Program</x-ui.button><x-ui.button :href="route('super-admin.program-batches.index')" variant="ghost" icon="bi-calendar-range">Batch</x-ui.button><x-ui.button :href="route('super-admin.institutions.create')" icon="bi-plus-lg">Tambah lembaga</x-ui.button></div></x-slot:actions>
</x-ui.page-header>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@error('institution')<div class="alert alert-danger">{{ $message }}</div>@enderror
<div class="skuad-table-wrap"><div class="table-responsive"><table class="table skuad-table">
    <thead><tr><th>Lembaga</th><th>Tipe</th><th>Alamat</th><th>Batch</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
    <tbody>
        @forelse($institutions as $institution)
            <tr>
                <td><strong>{{ $institution->name }}</strong><small class="d-block text-muted">{{ $institution->slug }}</small></td>
                <td>{{ str($institution->type)->headline() }}</td>
                <td>{{ $institution->address ?: '-' }}</td>
                <td>{{ $institution->batches_count }}</td>
                <td><x-ui.badge :variant="$institution->is_active ? 'success' : 'neutral'">{{ $institution->is_active ? 'Aktif' : 'Arsip' }}</x-ui.badge></td>
                <td class="text-end"><div class="d-inline-flex gap-1"><a class="skuad-icon-button" href="{{ route('super-admin.institutions.edit', $institution) }}" aria-label="Edit {{ $institution->name }}"><i class="bi bi-pencil"></i></a><form method="POST" action="{{ route('super-admin.institutions.destroy', $institution) }}" data-confirm="Hapus lembaga ini?">@csrf @method('DELETE')<button class="skuad-icon-button text-danger" aria-label="Hapus {{ $institution->name }}"><i class="bi bi-trash"></i></button></form></div></td>
            </tr>
        @empty
            <tr><td colspan="6"><x-ui.empty-state title="Belum ada lembaga" description="Tambahkan sekolah, komunitas, RKDD, atau mitra." icon="bi-buildings" /></td></tr>
        @endforelse
    </tbody>
</table></div></div>
<div class="mt-4">{{ $institutions->links() }}</div>
@endsection
