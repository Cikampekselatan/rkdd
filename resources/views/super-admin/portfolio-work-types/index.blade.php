@extends('layouts.dashboard')
@section('title', 'Jenis Karya Program')
@section('breadcrumb', 'Jenis Karya')
@section('content')
<x-ui.page-header eyebrow="Master portofolio" title="Jenis Karya per Program" description="Kelola pilihan jenis karya sesuai karakter tiap program RKDD. Filter portofolio dan form peserta membaca daftar ini.">
    <x-slot:actions><x-ui.button :href="route('super-admin.portfolio-work-types.create')" icon="bi-plus-lg">Tambah jenis karya</x-ui.button></x-slot:actions>
</x-ui.page-header>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
<form class="skuad-card p-3 mb-4" method="GET">
    <div class="row g-2 align-items-end">
        <div class="col-md-6"><label class="form-label" for="program_id">Filter program</label><select class="form-select" id="program_id" name="program_id"><option value="">Semua program</option>@foreach($programs as $program)<option value="{{ $program->id }}" @selected((int)$selectedProgramId === $program->id)>{{ $program->name }}</option>@endforeach</select></div>
        <div class="col-md-auto"><x-ui.button type="submit">Filter</x-ui.button></div>
    </div>
</form>
<div class="skuad-table-wrap"><div class="table-responsive"><table class="table skuad-table">
    <thead><tr><th>Jenis karya</th><th>Program</th><th>Slug</th><th>Urutan</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
    <tbody>
        @forelse($workTypes as $type)
            <tr>
                <td><strong>{{ $type->name }}</strong></td>
                <td>{{ $type->program?->name }}</td>
                <td><code>{{ $type->slug }}</code></td>
                <td>{{ $type->sort_order }}</td>
                <td><x-ui.badge :variant="$type->is_active ? 'success' : 'neutral'">{{ $type->is_active ? 'Aktif' : 'Nonaktif' }}</x-ui.badge></td>
                <td class="text-end"><div class="d-inline-flex">
                    <a class="skuad-icon-button" href="{{ route('super-admin.portfolio-work-types.edit', $type) }}" aria-label="Edit {{ $type->name }}"><i class="bi bi-pencil"></i></a>
                    <form method="POST" action="{{ route('super-admin.portfolio-work-types.destroy', $type) }}" data-confirm="Hapus jenis karya ini? Karya lama tetap tersimpan, tetapi opsi ini tidak muncul lagi.">@csrf @method('DELETE')<button class="skuad-icon-button text-danger" aria-label="Hapus {{ $type->name }}"><i class="bi bi-trash"></i></button></form>
                </div></td>
            </tr>
        @empty
            <tr><td colspan="6"><x-ui.empty-state title="Belum ada jenis karya" description="Tambahkan jenis karya sesuai program." icon="bi-tags" /></td></tr>
        @endforelse
    </tbody>
</table></div></div>
<div class="mt-4">{{ $workTypes->links() }}</div>
@endsection
