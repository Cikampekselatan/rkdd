@extends('layouts.dashboard')
@section('title', auth()->user()->hasRole(\App\Enums\RoleSlug::Coach) ? 'Dashboard Instruktur/Coach - SKUAD' : 'Dashboard Pembina - SKUAD')
@section('breadcrumb', auth()->user()->hasRole(\App\Enums\RoleSlug::Coach) ? 'Dashboard instruktur/coach' : 'Dashboard pembina')
@section('content')
@php
    $teacherProgramContext = app(\App\Services\ProgramContextService::class);
    $teacherParticipantLabel = $teacherProgramContext->participantLabel(auth()->user());
    $teacherActiveBatch = $teacherProgramContext->activeBatch(auth()->user());
    $attendanceMax = max(1, ...array_values($charts['attendance'] ?: [0]));
    $gradeMax = max(1, ...array_values($charts['grades'] ?: [0]));
    $competencyMax = max(1, ...array_values($charts['competencies'] ?: [0]));
@endphp
<div class="lead-dashboard-page">
    <section class="lead-dashboard-hero">
        <div><p>Pusat kendali pembinaan</p><h1>Selamat datang, {{ auth()->user()->name }}.</h1><span>{{ $teacherActiveBatch?->program?->name ?? 'Program aktif' }} · {{ $year?->name ?? 'Periode belum tersedia' }} · Data belajar, pendampingan, dan tindak lanjut dalam satu pandangan.</span></div>
        <form method="GET"><label for="year">Periode</label><select class="form-select" id="year" name="year" onchange="this.form.submit()">@forelse($years as $option)<option value="{{ $option->id }}" @selected($year?->id===$option->id)>{{ $option->name }}{{ $option->is_active?' · Aktif':'' }}</option>@empty<option>Belum tersedia</option>@endforelse</select></form>
    </section>

    <section class="lead-kpi-grid" aria-label="Indikator utama">
        @foreach([
            [$teacherParticipantLabel.' aktif',$kpis['active_students'],'bi-people','teal'],['Pendaftaran bulan ini',$kpis['new_registrations'],'bi-person-plus','cyan'],['Onboarding belum lengkap',$kpis['onboarding'],'bi-person-exclamation','orange'],['Pertemuan selesai',$kpis['sessions_completed'].' / '.$charts['progress']['total'],'bi-journal-check','navy'],['Tugas belum dinilai',$kpis['ungraded'],'bi-clipboard-data','orange'],['Revisi belum selesai',$kpis['revisions'],'bi-arrow-repeat','cyan'],['Kehadiran rata-rata',$kpis['attendance_rate'].'%','bi-calendar2-check','teal'],['Catatan terbuka',$kpis['open_notes'],'bi-exclamation-diamond','orange'],['Absen menunggu verifikasi',$kpis['pending_teacher_logs'],'bi-journal-text','navy']
        ] as [$label,$value,$icon,$tone])<article class="lead-kpi lead-kpi-{{ $tone }}"><span><i class="bi {{ $icon }}"></i></span><div><strong>{{ $value }}</strong><small>{{ $label }}</small></div></article>@endforeach
    </section>

    <section class="quick-action-panel"><div><p class="skuad-eyebrow">Aksi cepat</p><h2>Mulai pekerjaan penting</h2></div><div class="quick-action-grid"><a href="{{ route('teacher.learning.sessions.create') }}"><i class="bi bi-play-circle"></i><span>Buka pertemuan</span></a>@can('create', \App\Models\TeacherActivityLog::class)<a href="{{ route('activity-logs.create') }}"><i class="bi bi-journal-plus"></i><span>Isi absen pengajar</span></a>@else<a href="{{ route('activity-logs.index') }}"><i class="bi bi-vector-pen"></i><span>Tanda tangani absen</span></a>@endcan<a href="{{ route('teacher.attendance.index') }}"><i class="bi bi-calendar2-plus"></i><span>Ambil presensi</span></a><a href="{{ route('teacher.assignments.create') }}"><i class="bi bi-clipboard-plus"></i><span>Buat tugas</span></a><a href="{{ route('teacher.assignments.index') }}"><i class="bi bi-check2-square"></i><span>Nilai pengumpulan</span></a><a href="{{ route('documents.create') }}"><i class="bi bi-folder-plus"></i><span>Tambah dokumen</span></a>@can('create', \App\Models\ImportantNote::class)<a href="{{ route('important-notes.create') }}"><i class="bi bi-exclamation-square"></i><span>Buat catatan</span></a>@else<a href="{{ route('important-notes.index') }}"><i class="bi bi-vector-pen"></i><span>Paraf catatan</span></a>@endcan</div></section>

    <div class="lead-dashboard-columns">
        <section class="lead-panel attention-panel"><header><div><p class="skuad-eyebrow">Tindak lanjut</p><h2>{{ $teacherParticipantLabel }} perlu perhatian</h2></div><span>{{ $attentionStudents->count() }} {{ strtolower($teacherParticipantLabel) }}</span></header>
            <div class="attention-list">@forelse($attentionStudents as $item)<a href="{{ route('admin.students.show',$item['student']) }}"><x-ui.avatar :name="$item['student']->name" size="sm"/><div><strong>{{ $item['student']->name }}</strong><small>{{ $item['student']->studentProfile?->schoolClass?->name ?? 'Tanpa kelas' }}</small><p>@foreach($item['reasons'] as $reason)<span>{{ $reason }}</span>@endforeach</p></div><b>{{ $item['severity'] }}</b></a>@empty<div class="lead-empty"><i class="bi bi-shield-check"></i><strong>Belum ada indikator perhatian</strong><p>Daftar akan terisi dari kehadiran, tugas, revisi, dan nilai.</p></div>@endforelse</div>
        </section>
        @php($progressTotal = max(1, (int) $charts['progress']['total']))
        @php($progressPercent = (int) round($charts['progress']['completed'] / $progressTotal * 100))
        <section class="lead-panel progress-panel"><header><div><p class="skuad-eyebrow">Kurikulum</p><h2>Progress pertemuan program</h2></div><strong>{{ $charts['progress']['completed'] }}/{{ $charts['progress']['total'] }}</strong></header><div class="session-progress-ring" style="--lead-progress:{{ $progressPercent }}"><span><b>{{ $progressPercent }}%</b><small>selesai</small></span></div><p>{{ $charts['progress']['visible'] }} pertemuan sudah terlihat oleh siswa.</p><a class="btn btn-outline-primary" href="{{ route('teacher.learning.index') }}">Kelola pembelajaran</a></section>
    </div>

    <section class="dashboard-chart-grid">
        <article class="lead-panel"><header><div><p class="skuad-eyebrow">Konsistensi</p><h2>Kehadiran {{ strtolower($teacherParticipantLabel) }}</h2></div><strong>{{ $kpis['attendance_rate'] }}%</strong></header><div class="bar-chart">@foreach($charts['attendance'] as $label=>$value)<div><span>{{ \App\Enums\AttendanceStatus::from($label)->label() }}</span><i><b style="width:{{ $value/$attendanceMax*100 }}%"></b></i><strong>{{ $value }}</strong></div>@endforeach</div></article>
        <article class="lead-panel"><header><div><p class="skuad-eyebrow">Capaian</p><h2>Distribusi nilai</h2></div></header><div class="bar-chart chart-orange">@foreach($charts['grades'] as $label=>$value)<div><span>{{ $label }}</span><i><b style="width:{{ $value/$gradeMax*100 }}%"></b></i><strong>{{ $value }}</strong></div>@endforeach</div></article>
        <article class="lead-panel"><header><div><p class="skuad-eyebrow">Kompetensi</p><h2>Level pencapaian</h2></div></header><div class="bar-chart chart-navy">@foreach($charts['competencies'] as $label=>$value)<div><span>{{ $label }}</span><i><b style="width:{{ $value/$competencyMax*100 }}%"></b></i><strong>{{ $value }}</strong></div>@endforeach</div></article>
    </section>

    <div class="lead-dashboard-columns">
        <section class="lead-panel"><header><div><p class="skuad-eyebrow">Catatan penting</p><h2>Perlu ditindaklanjuti</h2></div><a href="{{ route('important-notes.index') }}">Lihat semua</a></header><div class="compact-monitor-list">@forelse($recentNotes as $note)<a href="{{ route('important-notes.show',$note) }}"><span class="priority-dot priority-{{ $note->priority->value }}"></span><div><strong>{{ Str::limit($note->note,70) }}</strong><small>{{ $note->note_date->translatedFormat('d M Y') }} · {{ $note->status->label() }}</small></div></a>@empty<div class="lead-empty"><p>Tidak ada catatan terbuka.</p></div>@endforelse</div></section>
        <section class="lead-panel"><header><div><p class="skuad-eyebrow">Absen pengajar</p><h2>Menunggu verifikasi</h2></div><a href="{{ route('activity-logs.index') }}">Lihat semua</a></header><div class="compact-monitor-list">@forelse($pendingLogs as $log)<a href="{{ route('activity-logs.show',$log) }}"><span class="monitor-number">{{ str_pad($log->log_number,2,'0',STR_PAD_LEFT) }}</span><div><strong>{{ $log->teacher->name }}</strong><small>{{ $log->activity_date->translatedFormat('d M Y') }} · {{ Str::limit($log->material,45) }}</small></div></a>@empty<div class="lead-empty"><p>Tidak ada absen yang menunggu verifikasi.</p></div>@endforelse</div></section>
    </div>
</div>
@endsection
