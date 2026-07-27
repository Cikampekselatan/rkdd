@extends('layouts.dashboard')
@section('title', 'Tahun Ajaran - SKUAD Learning Hub')
@section('breadcrumb', 'Tahun Ajaran')
@section('content')
<x-ui.page-header eyebrow="Master akademik" title="Tahun Ajaran" description="Pastikan tepat satu tahun ajaran aktif untuk seluruh workflow SKUAD.">
    <x-slot:actions><x-ui.button :href="route('admin.academic-years.create')" icon="bi-plus-lg">Tambah tahun</x-ui.button></x-slot:actions>
</x-ui.page-header>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@error('academic_year')<div class="alert alert-danger">{{ $message }}</div>@enderror
<div class="skuad-table-wrap"><div class="table-responsive"><table class="table skuad-table">
    <thead><tr><th>Periode/Tahun Ajaran</th><th>Rentang</th><th>Kelompok/Angkatan</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
    <tbody>
        @forelse($academicYears as $year)
            <tr>
                <td><strong>{{ $year->name }}</strong></td>
                <td>{{ $year->starts_on->format('d M Y') }} - {{ $year->ends_on->format('d M Y') }}</td>
                <td>{{ $year->classes_count }}</td>
                <td><x-ui.badge :variant="$year->is_active ? 'success' : 'neutral'">{{ $year->is_active ? 'Aktif' : 'Arsip' }}</x-ui.badge></td>
                <td class="text-end"><div class="d-inline-flex gap-1">
                    <a class="skuad-icon-button" href="{{ route('admin.academic-years.edit', $year) }}" aria-label="Edit tahun ajaran {{ $year->name }}"><i class="bi bi-pencil" aria-hidden="true"></i></a>
                    <form method="POST" action="{{ route('admin.academic-years.destroy', $year) }}" data-confirm="Hapus tahun ajaran ini?">@csrf @method('DELETE')<button class="skuad-icon-button text-danger" aria-label="Hapus tahun ajaran {{ $year->name }}"><i class="bi bi-trash" aria-hidden="true"></i></button></form>
                </div></td>
            </tr>
        @empty
            <tr><td colspan="5"><x-ui.empty-state title="Belum ada tahun ajaran" description="Tambahkan tahun ajaran untuk mulai membuat kelas." icon="bi-calendar3" /></td></tr>
        @endforelse
    </tbody>
</table></div></div>
<div class="mt-4">{{ $academicYears->links() }}</div>
@endsection
