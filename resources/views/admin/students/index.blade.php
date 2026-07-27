@extends('layouts.dashboard')
@php
    $studentProgramContext = app(\App\Services\ProgramContextService::class);
    $participantLabel = $studentProgramContext->participantLabel(auth()->user());
    $groupLabel = $studentProgramContext->groupLabel(auth()->user());
@endphp
@section('title', $participantLabel.' Program - RKDD')
@section('breadcrumb', $participantLabel)
@section('content')
<x-ui.page-header eyebrow="Keanggotaan program" :title="$participantLabel.' Program'" :description="'Pantau anggota program aktif, termasuk '.strtolower($participantLabel).' yang sudah tidak aktif atau berpindah kegiatan.'">
    <x-slot:actions><x-ui.button variant="outline" icon="bi-funnel" data-bs-toggle="offcanvas" data-bs-target="#studentFilter">Filter</x-ui.button></x-slot:actions>
</x-ui.page-header>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

<form class="skuad-card p-3 mb-3 d-none d-lg-flex gap-2" method="GET">
    <input class="form-control" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Cari nama, email, nomor peserta, atau kelas/lembaga asal">
    <select class="form-select" name="status"><option value="">Semua status</option>@foreach(\App\Enums\UserStatus::cases() as $status)<option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>{{ ucfirst($status->value) }}</option>@endforeach</select>
    <select class="form-select" name="grade_level"><option value="">Semua tingkat</option>@foreach([7, 8, 9] as $grade)<option value="{{ $grade }}" @selected((string) ($filters['grade_level'] ?? '') === (string) $grade)>Kelas {{ $grade }}</option>@endforeach</select>
    <x-ui.button type="submit" icon="bi-search">Cari</x-ui.button>
</form>

<div class="skuad-table-wrap"><div class="table-responsive"><table class="table skuad-table">
    <thead><tr><th>{{ $participantLabel }}</th><th>Kelas/Lembaga asal</th><th>{{ $groupLabel }}</th><th>Minat</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
    <tbody>
        @forelse($students as $student)
            <tr>
                <td><div class="d-flex align-items-center gap-2"><x-ui.avatar :name="$student->name" size="sm" /><div><strong class="d-block">{{ $student->name }}</strong><small class="text-secondary">{{ $student->studentProfile?->student_number ?? $student->email }}</small></div></div></td>
                <td>{{ $student->studentProfile?->grade_level ? 'Kelas '.$student->studentProfile->grade_level.' - '.$student->studentProfile->school_class_name : 'Belum dilengkapi' }}</td>
                <td>{{ $student->studentProfile?->schoolClass?->name ?? 'Belum dipilih' }}</td>
                <td><div class="d-flex flex-wrap gap-1">@forelse(array_slice($student->onboardingResponse?->interests ?? [], 0, 2) as $interest)<x-ui.badge>{{ ucfirst($interest) }}</x-ui.badge>@empty<span class="text-secondary">-</span>@endforelse</div></td>
                <td><x-ui.badge :variant="$student->status === \App\Enums\UserStatus::Active ? 'success' : ($student->status === \App\Enums\UserStatus::Suspended ? 'danger' : 'warning')">{{ ucfirst($student->status->value) }}</x-ui.badge></td>
                <td class="text-end"><a class="skuad-icon-button" href="{{ route('admin.students.show', $student) }}" aria-label="Detail {{ $student->name }}"><i class="bi bi-arrow-up-right" aria-hidden="true"></i></a></td>
            </tr>
        @empty
            <tr><td colspan="6"><x-ui.empty-state title="Peserta tidak ditemukan" description="Coba ubah pencarian atau filter yang digunakan." icon="bi-people" /></td></tr>
        @endforelse
    </tbody>
</table></div></div>

<div class="skuad-mobile-list">
    @foreach($students as $student)
        <article class="student-mobile-card"><div class="student-mobile-card-header"><x-ui.avatar :name="$student->name" size="sm" /><div class="flex-grow-1"><strong class="d-block">{{ $student->name }}</strong><small class="text-secondary">{{ $student->studentProfile?->grade_level ? 'Kelas '.$student->studentProfile->grade_level.' - '.$student->studentProfile->school_class_name : 'Onboarding' }}</small></div><x-ui.badge :variant="$student->status === \App\Enums\UserStatus::Active ? 'success' : 'warning'">{{ $student->status->value }}</x-ui.badge></div><div class="student-mobile-card-meta"><div><small>Kelompok</small><strong>{{ $student->studentProfile?->schoolClass?->name ?? '-' }}</strong></div><div><small>Onboarding</small><strong>{{ $student->onboardingResponse?->completed_at ? 'Lengkap' : 'Proses' }}</strong></div></div><x-ui.button :href="route('admin.students.show', $student)" variant="outline">Lihat detail</x-ui.button></article>
    @endforeach
</div>
<div class="mt-4">{{ $students->links() }}</div>
@endsection

@push('overlays')
<div class="offcanvas offcanvas-end" tabindex="-1" id="studentFilter"><div class="offcanvas-header border-bottom"><h2 class="offcanvas-title h5">Filter peserta</h2><button class="btn-close" type="button" data-bs-dismiss="offcanvas" aria-label="Tutup filter"></button></div><form method="GET" class="offcanvas-body d-flex flex-column">
    <div class="mb-3"><label class="form-label">Pencarian</label><input class="form-control" name="q" value="{{ $filters['q'] ?? '' }}"></div>
    <div class="mb-3"><label class="form-label">Status keanggotaan</label><select class="form-select" name="status"><option value="">Semua</option>@foreach(\App\Enums\UserStatus::cases() as $status)<option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>{{ ucfirst($status->value) }}</option>@endforeach</select></div>
    <div class="mb-3"><label class="form-label">Tingkat sekolah</label><select class="form-select" name="grade_level"><option value="">Semua tingkat</option>@foreach([7, 8, 9] as $grade)<option value="{{ $grade }}" @selected((string) ($filters['grade_level'] ?? '') === (string) $grade)>Kelas {{ $grade }}</option>@endforeach</select></div>
    <div class="mb-3"><label class="form-label">Minat</label><select class="form-select" name="interest"><option value="">Semua kategori</option>@foreach(['design', 'photography', 'video', 'presentation', 'ai', 'coding', 'data', 'entrepreneurship'] as $interest)<option value="{{ $interest }}" @selected(($filters['interest'] ?? '') === $interest)>{{ ucfirst($interest) }}</option>@endforeach</select></div>
    <div class="mb-3"><label class="form-label">Onboarding</label><select class="form-select" name="onboarding"><option value="">Semua</option><option value="complete" @selected(($filters['onboarding'] ?? '') === 'complete')>Lengkap</option><option value="incomplete" @selected(($filters['onboarding'] ?? '') === 'incomplete')>Belum lengkap</option></select></div>
    <div class="mt-auto d-grid gap-2"><x-ui.button type="submit">Terapkan</x-ui.button><x-ui.button :href="route('admin.students.index')" variant="ghost">Reset</x-ui.button></div>
</form></div>
@endpush
