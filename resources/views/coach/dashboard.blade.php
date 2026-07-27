@extends('layouts.dashboard')
@section('title', 'Dashboard Instruktur/Coach - SKUAD')
@section('breadcrumb', 'Dashboard instruktur/coach')
@section('content')
<div class="lead-dashboard-page coach-dashboard">
    <section class="lead-dashboard-hero principal-hero">
        <div>
            <p>Pusat monitoring instruktur/coach</p>
            <h1>Pendampingan yang terlihat dan dapat ditindaklanjuti.</h1>
            <span>{{ $year?->name ?? 'Tahun ajaran belum tersedia' }} - Ringkasan siswa, proyek, catatan, dan akuntabilitas pembina.</span>
        </div>
        <form method="GET">
            <label for="year">Tahun ajaran</label>
            <select class="form-select" id="year" name="year" onchange="this.form.submit()">
                @forelse($years as $option)
                    <option value="{{ $option->id }}" @selected($year?->id === $option->id)>{{ $option->name }}{{ $option->is_active ? ' - Aktif' : '' }}</option>
                @empty
                    <option>Belum tersedia</option>
                @endforelse
            </select>
        </form>
    </section>

    <section class="lead-kpi-grid" aria-label="Indikator monitoring instruktur/coach">
        @foreach([
            ['Siswa aktif', $kpis['active_students'], 'bi-people', 'teal'],
            ['Siswa perlu perhatian', $attentionStudents->count(), 'bi-person-exclamation', 'orange'],
            ['Kehadiran rata-rata', $kpis['attendance_rate'].'%', 'bi-calendar2-check', 'cyan'],
            ['Absen menunggu verifikasi', $kpis['pending_teacher_logs'], 'bi-journal-check', 'navy'],
            ['Catatan menunggu instruktur/coach', $coachSummary['notes_waiting'], 'bi-exclamation-diamond', 'orange'],
            ['Proyek terpantau', $coachSummary['projects'], 'bi-briefcase', 'teal'],
        ] as [$label, $value, $icon, $tone])
            <article class="lead-kpi lead-kpi-{{ $tone }}"><span><i class="bi {{ $icon }}" aria-hidden="true"></i></span><div><strong>{{ $value }}</strong><small>{{ $label }}</small></div></article>
        @endforeach
    </section>

    <section class="quick-action-panel">
        <div><p class="skuad-eyebrow">Akses cepat</p><h2>Ruang kerja pendampingan</h2></div>
        <div class="quick-action-grid">
            <a href="{{ route('admin.students.index') }}"><i class="bi bi-people" aria-hidden="true"></i><span>Monitor siswa</span></a>
            <a href="{{ route('activity-logs.index') }}"><i class="bi bi-journal-check" aria-hidden="true"></i><span>Verifikasi absen</span></a>
            <a href="{{ route('important-notes.index') }}"><i class="bi bi-exclamation-diamond" aria-hidden="true"></i><span>Catatan penting</span></a>
            <a href="{{ route('documents.index') }}"><i class="bi bi-folder2-open" aria-hidden="true"></i><span>Dokumen</span></a>
            <a href="{{ route('reports.index') }}"><i class="bi bi-bar-chart-line" aria-hidden="true"></i><span>Laporan</span></a>
        </div>
    </section>

    <div class="lead-dashboard-columns">
        <section class="lead-panel attention-panel">
            <header><div><p class="skuad-eyebrow">Pendampingan siswa</p><h2>Siswa perlu perhatian</h2></div><span>{{ $attentionStudents->count() }} siswa</span></header>
            <div class="attention-list">
                @forelse($attentionStudents as $item)
                    <a href="{{ route('admin.students.show', $item['student']) }}">
                        <x-ui.avatar :name="$item['student']->name" size="sm" />
                        <div><strong>{{ $item['student']->name }}</strong><small>{{ $item['student']->studentProfile?->schoolClass?->name ?? 'Tanpa kelas' }}</small><p>@foreach($item['reasons'] as $reason)<span>{{ $reason }}</span>@endforeach</p></div>
                        <b>{{ $item['severity'] }}</b>
                    </a>
                @empty
                    <div class="lead-empty"><i class="bi bi-shield-check" aria-hidden="true"></i><strong>Belum ada indikator perhatian</strong><p>Indikator berasal dari kehadiran, tugas, revisi, dan nilai siswa.</p></div>
                @endforelse
            </div>
        </section>

        <section class="lead-panel">
            <header><div><p class="skuad-eyebrow">Akuntabilitas pembina</p><h2>Menunggu verifikasi</h2></div><a href="{{ route('activity-logs.index') }}">Lihat semua</a></header>
            <div class="compact-monitor-list">
                @forelse($pendingLogs as $log)
                    <a href="{{ route('activity-logs.show', $log) }}"><span class="monitor-number">{{ str_pad($log->log_number, 2, '0', STR_PAD_LEFT) }}</span><div><strong>{{ $log->teacher->name }}</strong><small>{{ $log->activity_date->translatedFormat('d M Y') }} - {{ Str::limit($log->material, 42) }}</small></div></a>
                @empty
                    <div class="lead-empty"><p>Tidak ada absen yang menunggu verifikasi.</p></div>
                @endforelse
            </div>
        </section>
    </div>

    <div class="lead-dashboard-columns">
        <section class="lead-panel">
            <header><div><p class="skuad-eyebrow">Tindak lanjut</p><h2>Catatan menunggu instruktur/coach</h2></div><a href="{{ route('important-notes.index') }}">Lihat semua</a></header>
            <div class="compact-monitor-list">
                @forelse($notesWaiting as $note)
                    <a href="{{ route('important-notes.show', $note) }}"><span class="priority-dot priority-{{ $note->priority->value }}"></span><div><strong>{{ Str::limit($note->note, 75) }}</strong><small>{{ $note->note_date->translatedFormat('d M Y') }} - {{ $note->status->label() }}</small></div></a>
                @empty
                    <div class="lead-empty"><p>Semua catatan sudah ditindaklanjuti.</p></div>
                @endforelse
            </div>
        </section>

        <section class="lead-panel">
            <header><div><p class="skuad-eyebrow">Proyek siswa</p><h2>Karya terbaru terpantau</h2></div><strong>{{ $coachSummary['projects'] }}</strong></header>
            <div class="compact-monitor-list">
                @forelse($recentProjects as $project)
                    <div class="monitor-static"><span class="monitor-number"><i class="bi bi-briefcase" aria-hidden="true"></i></span><div><strong>{{ Str::limit($project->title, 60) }}</strong><small>{{ $project->owner->name }} - {{ $project->schoolClass->name }}</small></div></div>
                @empty
                    <div class="lead-empty"><p>Belum ada proyek yang dapat dipantau.</p></div>
                @endforelse
            </div>
        </section>
    </div>
</div>
@endsection
