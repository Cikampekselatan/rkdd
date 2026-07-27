@extends('layouts.dashboard')
@section('title', 'Dashboard Super Admin - RKDD')
@section('breadcrumb', 'Kontrol sistem')
@section('content')
@php($roleMax = max(1, $roleCounts->max('users_count') ?? 0))
<div class="admin-command-page">
    <section class="lead-dashboard-hero admin-command-hero super-admin-hero">
        <div><p>System command center</p><h1>Kendali penuh RKDD Cikampek Selatan.</h1><span>{{ $activeYear?->name ?? 'Periode aktif belum tersedia' }} - Pantau program, lembaga, peserta, keamanan autentikasi, role, dan kesehatan aplikasi.</span></div>
        <a class="btn btn-warning btn-lg" href="{{ route('admin.staff.index') }}"><i class="bi bi-person-gear" aria-hidden="true"></i> Kelola akses</a>
    </section>

    <section class="lead-kpi-grid" aria-label="Ringkasan sistem">
        @foreach([
            ['Total pengguna', $kpis['users'], 'bi-people', 'navy'],
            ['Program aktif', $kpis['programs'], 'bi-diagram-3', 'teal'],
            ['Lembaga aktif', $kpis['institutions'], 'bi-buildings', 'cyan'],
            ['Batch aktif', $kpis['program_batches'], 'bi-calendar-range', 'orange'],
            ['Pengguna aktif', $kpis['active_users'], 'bi-person-check', 'teal'],
            ['Onboarding', $kpis['onboarding'], 'bi-person-dash', 'cyan'],
            ['Ditangguhkan', $kpis['suspended'], 'bi-person-lock', 'orange'],
            ['Kelompok aktif', $kpis['classes'], 'bi-door-open', 'teal'],
            ['Pertemuan tercatat', $kpis['sessions'], 'bi-journal-richtext', 'navy'],
            ['Dokumen published', $kpis['documents'], 'bi-folder-check', 'cyan'],
            ['Kode tersedia', $kpis['codes'], 'bi-key', 'orange'],
            ['Penolakan login 24 jam', $kpis['auth_rejections'], 'bi-shield-exclamation', 'orange'],
        ] as [$label, $value, $icon, $tone])
            <article class="lead-kpi lead-kpi-{{ $tone }}"><span><i class="bi {{ $icon }}" aria-hidden="true"></i></span><div><strong>{{ $value }}</strong><small>{{ $label }}</small></div></article>
        @endforeach
    </section>

    <section class="quick-action-panel">
        <div><p class="skuad-eyebrow">Kontrol cepat</p><h2>Akses seluruh area sistem</h2></div>
        <div class="quick-action-grid">
            <a href="{{ route('admin.staff.index') }}"><i class="bi bi-person-gear" aria-hidden="true"></i><span>Staff & role</span></a>
            <a href="{{ route('super-admin.programs.index') }}"><i class="bi bi-diagram-3" aria-hidden="true"></i><span>Program RKDD</span></a>
            <a href="{{ route('super-admin.institutions.index') }}"><i class="bi bi-buildings" aria-hidden="true"></i><span>Lembaga</span></a>
            <a href="{{ route('super-admin.program-batches.index') }}"><i class="bi bi-calendar-range" aria-hidden="true"></i><span>Batch program</span></a>
            <a href="{{ route('admin.students.index') }}"><i class="bi bi-people" aria-hidden="true"></i><span>Peserta Program</span></a>
            <a href="{{ route('admin.academic-years.index') }}"><i class="bi bi-calendar3" aria-hidden="true"></i><span>Periode</span></a>
            <a href="{{ route('admin.registration-codes.index') }}"><i class="bi bi-key" aria-hidden="true"></i><span>Kode daftar</span></a>
            <a href="{{ route('documents.index') }}"><i class="bi bi-folder2-open" aria-hidden="true"></i><span>Dokumen</span></a>
            <a href="{{ route('reports.index') }}"><i class="bi bi-bar-chart-line" aria-hidden="true"></i><span>Laporan</span></a>
            <a href="{{ route('super-admin.design-system') }}"><i class="bi bi-palette2" aria-hidden="true"></i><span>Design system</span></a>
        </div>
    </section>

    <section class="lead-panel">
        <header>
            <div><p class="skuad-eyebrow">Lintas program</p><h2>Operasional per program</h2></div>
            <a href="{{ route('super-admin.program-batches.index') }}">Kelola batch</a>
        </header>
        <div class="admin-status-strip">
            @forelse($programBatchSummaries as $summary)
                @php($batch = $summary['batch'])
                <article>
                    <small>{{ $batch->program?->name }} · {{ $batch->institution?->name }}</small>
                    <strong>{{ $batch->period_label }}</strong>
                    <p class="small text-secondary mb-0">{{ $summary['participants'] }} peserta · {{ $summary['groups'] }} kelompok · {{ $summary['sessions'] }} pertemuan · {{ $summary['codes'] }} kode aktif</p>
                </article>
            @empty
                <article><small>Status</small><strong>Belum ada program aktif</strong></article>
            @endforelse
        </div>
    </section>

    <div class="admin-dashboard-grid">
        <section class="lead-panel"><header><div><p class="skuad-eyebrow">Distribusi akses</p><h2>Pengguna berdasarkan role</h2></div></header><div class="role-distribution">
            @foreach($roleCounts as $role)
                <article><div><span>{{ $role->name }}</span><i><span style="width: {{ $role->users_count / $roleMax * 100 }}%"></span></i></div><strong>{{ $role->users_count }}</strong></article>
            @endforeach
        </div></section>

        <section class="lead-panel"><header><div><p class="skuad-eyebrow">Kesehatan aplikasi</p><h2>Runtime dan penyimpanan</h2></div></header><div class="system-health-grid">
            @foreach([
                ['Environment', $system['environment']], ['Debug', $system['debug'] ? 'Aktif' : 'Nonaktif'], ['Laravel', $system['laravel']], ['PHP', $system['php']], ['Database', $system['database']], ['Session', $system['session']], ['Session encrypt', $system['session_encrypted'] ? 'Aktif' : 'Nonaktif'], ['Cache', $system['cache']], ['Queue', $system['queue']],
            ] as [$label, $value])
                <article><small>{{ $label }}</small><strong>{{ $value }}</strong></article>
            @endforeach
        </div></section>
    </div>

    <section class="lead-panel"><header><div><p class="skuad-eyebrow">Audit autentikasi</p><h2>Aktivitas login terbaru</h2></div></header><div class="compact-monitor-list">
        @forelse($recentAuthentication as $log)
            <div class="monitor-static"><span class="monitor-number"><i class="bi bi-{{ str_starts_with($log->event, 'rejected_') || $log->event === 'provider_error' ? 'shield-x' : 'shield-check' }}" aria-hidden="true"></i></span><div><strong>{{ $log->user?->name ?? $log->email ?? 'Pengguna tidak dikenal' }}</strong><small>{{ $log->created_at?->translatedFormat('d M Y H:i') }} - {{ $log->ip_address ?? 'IP tidak tersedia' }}</small></div><span class="auth-event {{ str_starts_with($log->event, 'rejected_') || $log->event === 'provider_error' ? 'is-rejected' : 'is-success' }}">{{ str($log->event)->replace('_', ' ') }}</span></div>
        @empty
            <div class="lead-empty"><p>Belum ada aktivitas autentikasi.</p></div>
        @endforelse
    </div></section>
</div>
@endsection
