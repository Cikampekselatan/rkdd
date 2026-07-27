@extends('layouts.dashboard')

@php
    $attendanceProgramContext = app(\App\Services\ProgramContextService::class);
    $participantLabel = $attendanceProgramContext->participantLabel(auth()->user());
    $groupLabel = $attendanceProgramContext->groupLabel(auth()->user());
    $periodLabel = $attendanceProgramContext->periodLabel(auth()->user());
@endphp

@section('title', 'Presensi '.$participantLabel.' - RKDD')
@section('breadcrumb', 'Presensi '.$participantLabel)

@section('content')
    <div class="attendance-page">
        <x-ui.page-header eyebrow="Kehadiran" :title="'Presensi '.$participantLabel" :description="'Buka presensi per pertemuan, catat seluruh '.strtolower($participantLabel).', lalu tutup sesi untuk memfinalkan rekap.'">
            <x-slot:actions>
                <button class="btn btn-primary skuad-touch-button" type="button" data-bs-toggle="collapse" data-bs-target="#openAttendanceForm" aria-expanded="{{ $errors->any() ? 'true' : 'false' }}">
                    <i class="bi bi-calendar2-plus" aria-hidden="true"></i> Buka sesi
                </button>
            </x-slot:actions>
        </x-ui.page-header>

        @if (session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
        @if ($errors->any())
            <div class="alert alert-danger" role="alert"><strong>Data belum dapat diproses.</strong> Periksa isian yang ditandai.</div>
        @endif

        <section class="attendance-filter-card">
            <form method="GET" action="{{ route('teacher.attendance.index') }}" class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label class="form-label" for="academic_year_id">{{ $periodLabel }}</label>
                    <select class="form-select" id="academic_year_id" name="academic_year_id" data-attendance-filter>
                        @foreach ($academicYears as $year)
                            <option value="{{ $year->id }}" @selected($academicYearId === $year->id)>{{ $year->name }}{{ $year->is_active ? ' - Aktif' : '' }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label" for="class_id">{{ $groupLabel }}</label>
                    <select class="form-select" id="class_id" name="class_id">
                        @foreach ($classes as $class)
                            <option value="{{ $class->id }}" @selected($classId === $class->id)>{{ $class->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2"><button class="btn btn-outline-primary w-100 skuad-touch-button" type="submit">Terapkan</button></div>
            </form>
        </section>

        <section id="openAttendanceForm" class="collapse {{ $errors->any() ? 'show' : '' }} attendance-open-card">
            <div class="attendance-open-copy">
                <span><i class="bi bi-lightning-charge-fill"></i></span>
                <div><p class="skuad-eyebrow">Mulai cepat</p><h2>Buka sesi presensi</h2><p>Semua {{ strtolower($participantLabel) }} aktif diberi status awal Hadir agar input di ponsel lebih cepat.</p></div>
            </div>
            <form method="POST" action="{{ route('teacher.attendance.store') }}" class="row g-3">
                @csrf
                <input type="hidden" name="class_id" value="{{ old('class_id', $classId) }}">
                <div class="col-lg-7">
                    <label class="form-label" for="learning_session_id">Pertemuan</label>
                    <select class="form-select @error('learning_session_id') is-invalid @enderror" id="learning_session_id" name="learning_session_id" required>
                        <option value="">Pilih pertemuan</option>
                        @foreach ($learningSessions as $learningSession)
                            <option value="{{ $learningSession->id }}" @selected((int) old('learning_session_id') === $learningSession->id)>Pertemuan {{ $learningSession->session_number }} - {{ $learningSession->title }}</option>
                        @endforeach
                    </select>
                    @error('learning_session_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    @error('class_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="col-lg-5">
                    <label class="form-label" for="attendance_date">Tanggal pelaksanaan</label>
                    <input class="form-control @error('attendance_date') is-invalid @enderror" id="attendance_date" name="attendance_date" type="date" value="{{ old('attendance_date', today()->toDateString()) }}" max="{{ today()->toDateString() }}" required>
                    @error('attendance_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <label class="form-label" for="notes">Catatan sesi <span class="text-secondary">(opsional)</span></label>
                    <textarea class="form-control" id="notes" name="notes" rows="2" maxlength="2000">{{ old('notes') }}</textarea>
                </div>
                <div class="col-12 d-flex justify-content-end"><button class="btn btn-primary skuad-touch-button" type="submit"><i class="bi bi-play-circle"></i> Buka dan isi presensi</button></div>
            </form>
        </section>

        <div class="row g-4 mt-1">
            <div class="col-xl-7">
                <section class="attendance-section-card h-100">
                    <div class="attendance-section-heading"><div><p class="skuad-eyebrow">Per pertemuan</p><h2>Sesi terbaru</h2></div><span>{{ $sessions->total() }} sesi</span></div>
                    <div class="attendance-session-list">
                        @forelse ($sessions as $session)
                            @php
                                $present = $session->records->whereIn('status', [\App\Enums\AttendanceStatus::Present, \App\Enums\AttendanceStatus::Late])->count();
                                $percentage = $session->records->count() ? (int) round(($present / $session->records->count()) * 100) : 0;
                            @endphp
                            <a class="attendance-session-row" href="{{ route('teacher.attendance.show', $session) }}">
                                <span class="attendance-session-number">{{ str_pad($session->learningSession->session_number, 2, '0', STR_PAD_LEFT) }}</span>
                                <span class="attendance-session-main"><small>{{ $session->attendance_date->translatedFormat('d M Y') }} · {{ $session->schoolClass->name }}</small><strong>{{ $session->learningSession->title }}</strong><em>{{ $session->records->count() }} {{ strtolower($participantLabel) }} tercatat</em></span>
                                <span class="attendance-session-result"><strong>{{ $percentage }}%</strong><small>{{ $session->status->label() }}</small></span>
                                <i class="bi bi-chevron-right"></i>
                            </a>
                        @empty
                            <x-ui.empty-state title="Belum ada sesi presensi" :description="'Pilih '.$periodLabel.' dan '.$groupLabel.', lalu buka sesi untuk pertemuan pertama.'" icon="bi-calendar2-check" />
                        @endforelse
                    </div>
                    @if ($sessions->hasPages())<div class="mt-4">{{ $sessions->links() }}</div>@endif
                </section>
            </div>

            <div class="col-xl-5">
                <section class="attendance-section-card h-100">
                    <div class="attendance-section-heading"><div><p class="skuad-eyebrow">Per {{ strtolower($participantLabel) }}</p><h2>Rekap {{ strtolower($groupLabel) }}</h2></div><span>Sesi ditutup</span></div>
                    <div class="attendance-student-recap">
                        @forelse ($studentRecap as $row)
                            <div class="attendance-recap-row">
                                <x-ui.avatar :name="$row['student']->name" size="sm" />
                                <div><strong>{{ $row['student']->name }}</strong><small>{{ $row['attended'] }} hadir dari {{ $row['total'] }} sesi final</small></div>
                                <span>{{ $row['percentage'] }}%</span>
                            </div>
                        @empty
                            <x-ui.empty-state title="Rekap belum tersedia" :description="'Rekap '.strtolower($participantLabel).' dihitung setelah sesi presensi ditutup.'" icon="bi-bar-chart" />
                        @endforelse
                    </div>
                </section>
            </div>
        </div>
    </div>
@endsection
