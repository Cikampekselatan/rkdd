@extends('layouts.dashboard')
@php
    $classProgramContext = app(\App\Services\ProgramContextService::class);
    $activeBatch = $classProgramContext->activeBatch(auth()->user());
    $groupLabel = $classProgramContext->groupLabel(auth()->user());
    $periodLabel = $classProgramContext->periodLabel(auth()->user());
@endphp
@section('title', $groupLabel.' - RKDD')
@section('breadcrumb', $groupLabel)
@section('content')
<x-ui.page-header eyebrow="Keanggotaan program" :title="$groupLabel" :description="'Sedang menampilkan '.$activeBatch?->program?->name.' · '.$activeBatch?->institution?->name.' · '.$activeBatch?->period_label.'. Ganti program aktif di header jika ingin melihat kelompok program lain.'">
    <x-slot:actions><x-ui.button :href="route('admin.classes.create')" icon="bi-plus-lg">Tambah kelompok</x-ui.button></x-slot:actions>
</x-ui.page-header>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@error('school_class')<div class="alert alert-danger">{{ $message }}</div>@enderror
<div class="row g-3">
    @forelse($classes as $class)
        <div class="col-md-6 col-xl-4"><article class="skuad-card h-100 p-4">
            <div class="d-flex justify-content-between gap-3"><div>
                <x-ui.badge :variant="$class->academicYear->is_active ? 'success' : 'neutral'">{{ $class->academicYear->name }}</x-ui.badge>
                <h2 class="h4 fw-bold mt-3 mb-1">{{ $class->name }}</h2>
                <p class="text-secondary mb-0">Kode {{ $class->code }} - {{ $periodLabel }}</p>
            </div><span class="phase-number" style="font-size:2.7rem">{{ $class->student_profiles_count }}</span></div>
            <hr>
            <p class="small text-secondary"><i class="bi bi-person-badge me-1" aria-hidden="true"></i>{{ $class->homeroomTeacher?->name ?? 'Koordinator belum ditentukan' }}</p>
            <div class="d-flex justify-content-between align-items-center">
                <x-ui.badge :variant="$class->is_active ? 'success' : 'neutral'">{{ $class->is_active ? 'Aktif' : 'Nonaktif' }}</x-ui.badge>
                <div class="d-flex">
                    <a class="skuad-icon-button" href="{{ route('admin.classes.edit', $class) }}" aria-label="Edit kelompok {{ $class->name }}"><i class="bi bi-pencil" aria-hidden="true"></i></a>
                    <form method="POST" action="{{ route('admin.classes.destroy', $class) }}" data-confirm="Hapus kelompok ini?">@csrf @method('DELETE')<button class="skuad-icon-button text-danger" aria-label="Hapus kelompok {{ $class->name }}"><i class="bi bi-trash" aria-hidden="true"></i></button></form>
                </div>
            </div>
        </article></div>
    @empty
        <div class="col-12"><div class="skuad-card"><x-ui.empty-state :title="'Belum ada '.$groupLabel" description="Tambahkan kelompok/angkatan untuk program aktif." icon="bi-people" /></div></div>
    @endforelse
</div>
<div class="mt-4">{{ $classes->links() }}</div>
@endsection
