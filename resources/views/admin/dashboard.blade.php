@extends('layouts.dashboard')
@section('title', 'Dashboard Admin Sekolah - SKUAD')
@section('breadcrumb', 'Dashboard admin')
@section('content')
@php
    $adminProgramContext = app(\App\Services\ProgramContextService::class);
    $adminParticipantLabel = $adminProgramContext->participantLabel(auth()->user());
    $adminActiveBatch = $adminProgramContext->activeBatch(auth()->user());
@endphp
<div class="admin-command-page">
    <section class="lead-dashboard-hero admin-command-hero">
        <div><p>Administrasi program</p><h1>Data program yang rapi, keputusan yang lebih cepat.</h1><span>{{ $adminActiveBatch?->program?->name ?? 'Semua program' }} · {{ $activeYear?->name ?? 'Periode aktif belum tersedia' }} - Kelola peserta, staff, kelompok/angkatan, dan akses program dari satu pusat.</span></div>
        <a class="btn btn-light btn-lg" href="{{ route('admin.students.index') }}"><i class="bi bi-people" aria-hidden="true"></i> Kelola {{ strtolower($adminParticipantLabel) }}</a>
    </section>

    <section class="lead-kpi-grid" aria-label="Ringkasan administrasi">
        @foreach([
            [$adminParticipantLabel.' aktif', $kpis['active_students'], 'bi-people', 'teal'],
            ['Masih onboarding', $kpis['onboarding_students'], 'bi-person-dash', 'orange'],
            ['Staff aktif', $kpis['active_staff'], 'bi-person-badge', 'navy'],
            ['Kelompok aktif', $kpis['active_classes'], 'bi-people', 'cyan'],
            ['Kode tersedia', $kpis['available_codes'], 'bi-key', 'orange'],
            ['Dokumen aktif', $kpis['active_documents'], 'bi-folder2-open', 'teal'],
        ] as [$label, $value, $icon, $tone])
            <article class="lead-kpi lead-kpi-{{ $tone }}"><span><i class="bi {{ $icon }}" aria-hidden="true"></i></span><div><strong>{{ $value }}</strong><small>{{ $label }}</small></div></article>
        @endforeach
    </section>

    <section class="quick-action-panel">
        <div><p class="skuad-eyebrow">Aksi utama</p><h2>Kelola fondasi program</h2></div>
        <div class="quick-action-grid">
            <a href="{{ route('admin.students.index') }}"><i class="bi bi-people" aria-hidden="true"></i><span>Peserta Program</span></a>
            <a href="{{ route('admin.staff.index') }}"><i class="bi bi-person-badge" aria-hidden="true"></i><span>Staff</span></a>
            <a href="{{ route('admin.classes.index') }}"><i class="bi bi-people" aria-hidden="true"></i><span>Kelompok/Angkatan</span></a>
            <a href="{{ route('admin.academic-years.index') }}"><i class="bi bi-calendar3" aria-hidden="true"></i><span>Periode</span></a>
            <a href="{{ route('admin.registration-codes.index') }}"><i class="bi bi-key" aria-hidden="true"></i><span>Kode pendaftaran</span></a>
            <a href="{{ route('documents.index') }}"><i class="bi bi-folder2-open" aria-hidden="true"></i><span>Dokumen</span></a>
            <a href="{{ route('reports.index') }}"><i class="bi bi-bar-chart-line" aria-hidden="true"></i><span>Laporan</span></a>
        </div>
    </section>

    @if($alerts->isNotEmpty())
        <section class="lead-panel"><header><div><p class="skuad-eyebrow">Perlu dibereskan</p><h2>Pemeriksaan data administrasi</h2></div></header><div class="admin-alert-stack">@foreach($alerts as $alert)<div class="admin-alert-item"><i class="bi bi-exclamation-triangle" aria-hidden="true"></i><span>{{ $alert }}</span></div>@endforeach</div></section>
    @endif

    <div class="admin-dashboard-grid">
        <section class="lead-panel"><header><div><p class="skuad-eyebrow">Kelompok aktif</p><h2>Kapasitas dan koordinator</h2></div><a href="{{ route('admin.classes.index') }}">Kelola kelompok</a></header><div class="admin-class-list">
            @forelse($classes as $class)
                <a class="admin-class-row" href="{{ route('admin.classes.edit', $class) }}"><div><strong>{{ $class->name }}</strong><small>{{ $class->homeroomTeacher?->name ?? 'Koordinator belum ditetapkan' }} - Kapasitas {{ $class->capacity }}</small></div><span>{{ $class->student_profiles_count }}</span></a>
            @empty
                <div class="lead-empty"><p>Belum ada kelompok/angkatan pada periode aktif.</p></div>
            @endforelse
        </div></section>

        <section class="lead-panel"><header><div><p class="skuad-eyebrow">{{ $adminParticipantLabel }} terbaru</p><h2>Pendaftaran terkini</h2></div><a href="{{ route('admin.students.index') }}">Lihat semua</a></header><div class="compact-monitor-list">
            @forelse($recentStudents as $student)
                <a href="{{ route('admin.students.show', $student) }}"><x-ui.avatar :name="$student->name" size="sm" /><div><strong>{{ $student->name }}</strong><small>{{ $student->studentProfile?->schoolClass?->name ?? 'Belum memilih kelas' }} - {{ ucfirst($student->status->value) }}</small></div></a>
            @empty
                <div class="lead-empty"><p>Belum ada {{ strtolower($adminParticipantLabel) }} terdaftar.</p></div>
            @endforelse
        </div></section>
    </div>

    <section class="lead-panel"><header><div><p class="skuad-eyebrow">Akses onboarding</p><h2>Kode pendaftaran yang dapat digunakan</h2></div><a href="{{ route('admin.registration-codes.index') }}">Kelola kode</a></header><div class="admin-status-strip">
        @forelse($registrationCodes as $code)
            <article><small>{{ $code->schoolClass?->name ?? 'Semua kelas' }}</small><strong>{{ $code->name }}</strong><p class="small text-secondary mb-0">{{ $code->used_count }}{{ $code->max_uses ? ' / '.$code->max_uses : '' }} digunakan</p></article>
        @empty
            <article><small>Status</small><strong>Belum ada kode aktif</strong></article>
        @endforelse
    </div></section>
</div>
@endsection
