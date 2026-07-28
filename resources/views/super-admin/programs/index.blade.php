@extends('layouts.dashboard')
@section('title', 'Program RKDD')
@section('breadcrumb', 'Program RKDD')
@section('content')
<x-ui.page-header eyebrow="Master platform" title="Program RKDD" description="Kelola jenis kegiatan seperti SKUAD, Konten Kreator, Jurnalis Digital, Affiliate UMKM, dan pelatihan lain.">
    <x-slot:actions>
        <div class="d-flex flex-wrap gap-2">
            <x-ui.button :href="route('super-admin.institutions.index')" variant="ghost" icon="bi-buildings">Lembaga</x-ui.button>
            <x-ui.button :href="route('super-admin.program-batches.index')" variant="ghost" icon="bi-calendar-range">Batch</x-ui.button>
            <x-ui.button :href="route('super-admin.portfolio-work-types.index')" variant="ghost" icon="bi-tags">Jenis Karya</x-ui.button>
            <x-ui.button :href="route('super-admin.programs.create')" icon="bi-plus-lg">Tambah program</x-ui.button>
        </div>
    </x-slot:actions>
</x-ui.page-header>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@error('program')<div class="alert alert-danger">{{ $message }}</div>@enderror
<div class="skuad-table-wrap"><div class="table-responsive"><table class="table skuad-table">
    <thead><tr><th>Program</th><th>Tipe</th><th>Tema</th><th>Sekolah/Lembaga</th><th>Batch</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
    <tbody>
        @forelse($programs as $program)
            <tr>
                <td><div class="program-name-cell">@if($program->logo_path)<img src="{{ route('program.assets', [$program, 'logo']) }}" alt="" aria-hidden="true">@else<span>{{ str($program->name)->substr(0, 1)->upper() }}</span>@endif<div><strong>{{ $program->name }}</strong><small class="d-block text-muted">{{ $program->slug }}</small></div></div></td>
                <td>{{ str($program->type)->headline() }}</td>
                <td><span class="program-theme-chip" style="--program-primary:{{ $program->primary_color }};--program-secondary:{{ $program->secondary_color }};--program-accent:{{ $program->accent_color }}"><i></i><span>Preview</span></span></td>
                <td>
                    @php($primaryInstitutionId = (int) ($program->firstBatch?->institution_id ?? 0))
                    <form class="program-institution-inline" method="POST" action="{{ route('super-admin.programs.update', $program) }}">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="name" value="{{ $program->name }}">
                        <input type="hidden" name="slug" value="{{ $program->slug }}">
                        <input type="hidden" name="type" value="{{ $program->type }}">
                        <input type="hidden" name="description" value="{{ $program->description }}">
                        <input type="hidden" name="primary_color" value="{{ $program->primary_color }}">
                        <input type="hidden" name="secondary_color" value="{{ $program->secondary_color }}">
                        <input type="hidden" name="accent_color" value="{{ $program->accent_color }}">
                        <input type="hidden" name="is_active" value="{{ $program->is_active ? '1' : '0' }}">
                        <label class="visually-hidden" for="institution_id_{{ $program->id }}">Sekolah/lembaga {{ $program->name }}</label>
                        <select class="form-select form-select-sm" id="institution_id_{{ $program->id }}" name="institution_id">
                            <option value="">Pilih lembaga</option>
                            @foreach($institutions as $institution)
                                <option value="{{ $institution->id }}" @selected($primaryInstitutionId === $institution->id)>{{ $institution->name }}</option>
                            @endforeach
                        </select>
                        <button class="skuad-icon-button" type="submit" aria-label="Simpan sekolah/lembaga {{ $program->name }}"><i class="bi bi-check2"></i></button>
                    </form>
                    @if($program->batches_count > $program->batches->count())
                        <span class="program-institution-pill">+{{ $program->batches_count - $program->batches->count() }} lainnya</span>
                    @endif
                </td>
                <td>{{ $program->batches_count }}</td>
                <td><x-ui.badge :variant="$program->is_active ? 'success' : 'neutral'">{{ $program->is_active ? 'Aktif' : 'Arsip' }}</x-ui.badge></td>
                <td class="text-end"><div class="d-inline-flex gap-1">
                    <a class="skuad-icon-button" href="{{ route('super-admin.programs.edit', $program) }}" aria-label="Edit {{ $program->name }}"><i class="bi bi-pencil"></i></a>
                    <form method="POST" action="{{ route('super-admin.programs.destroy', $program) }}" data-confirm="Hapus program ini?">@csrf @method('DELETE')<button class="skuad-icon-button text-danger" aria-label="Hapus {{ $program->name }}"><i class="bi bi-trash"></i></button></form>
                </div></td>
            </tr>
        @empty
            <tr><td colspan="7"><x-ui.empty-state title="Belum ada program" description="Tambahkan program pertama RKDD." icon="bi-diagram-3" /></td></tr>
        @endforelse
    </tbody>
</table></div></div>
<div class="mt-4">{{ $programs->links() }}</div>
@endsection
