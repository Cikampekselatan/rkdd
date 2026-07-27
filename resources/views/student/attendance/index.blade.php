@extends('layouts.dashboard')

@section('title', 'Kehadiran Saya - SKUAD Learning Hub')
@section('breadcrumb', 'Kehadiran saya')

@section('content')
    <div class="attendance-page student-attendance-page">
        <section class="student-attendance-hero">
            <div><p class="skuad-eyebrow">Rekam jejak pribadi</p><h1>Kehadiran saya</h1><p>Riwayat hanya menampilkan sesi yang sudah difinalkan oleh pembina.</p></div>
            <div class="student-attendance-score"><strong>{{ $summary['percentage'] }}%</strong><span>{{ $summary['attended'] }} dari {{ $summary['total'] }} pertemuan</span></div>
        </section>

        <section class="attendance-section-card attendance-scan-entry">
            <div>
                <p class="skuad-eyebrow">Presensi hari ini</p>
                <h2>Scan QR dari layar kelas</h2>
                <p>Siswa hanya bisa check-in jika hadir di ruang kelas dan memindai QR yang sedang ditampilkan guru/coach.</p>
            </div>
            <a class="btn btn-primary skuad-touch-button" href="{{ route('student.attendance.scan') }}">
                <i class="bi bi-qr-code-scan"></i> Buka scanner
            </a>
        </section>

        <div class="attendance-count-strip" aria-label="Ringkasan kehadiran pribadi">
            @foreach ($statuses as $status)
                <div class="attendance-count attendance-tone-{{ $status->value }}"><i class="bi {{ $status->icon() }}"></i><span><strong>{{ $summary['counts'][$status->value] }}</strong><small>{{ $status->label() }}</small></span></div>
            @endforeach
        </div>

        <section class="attendance-section-card">
            <div class="attendance-section-heading"><div><p class="skuad-eyebrow">Sesi final</p><h2>Riwayat presensi</h2></div><span>{{ $records->total() }} catatan</span></div>
            <div class="student-attendance-history">
                @forelse ($records as $record)
                    <article>
                        <span class="student-attendance-number">{{ str_pad($record->attendanceSession->learningSession->session_number, 2, '0', STR_PAD_LEFT) }}</span>
                        <div><small>{{ $record->attendanceSession->attendance_date->translatedFormat('l, d M Y') }} · {{ $record->attendanceSession->schoolClass->name }}</small><h3>{{ $record->attendanceSession->learningSession->title }}</h3><p>{{ $record->notes ?: 'Tidak ada catatan khusus.' }}</p></div>
                        <span class="attendance-final-status attendance-tone-{{ $record->status->value }}"><i class="bi {{ $record->status->icon() }}"></i> {{ $record->status->label() }}</span>
                    </article>
                @empty
                    <x-ui.empty-state title="Belum ada riwayat kehadiran" description="Catatan akan muncul setelah pembina menutup sesi presensi pertamamu." icon="bi-calendar2-heart" />
                @endforelse
            </div>
            @if ($records->hasPages())<div class="mt-4">{{ $records->links() }}</div>@endif
        </section>
    </div>
@endsection
