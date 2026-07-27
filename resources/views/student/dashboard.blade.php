@extends('layouts.dashboard')

@section('title', 'Dashboard Peserta - RKDD Learning Hub')
@section('breadcrumb', 'Dashboard pribadi')

@section('content')
    @php
        $studentProgramContext = app(\App\Services\ProgramContextService::class);
        $studentParticipantLabel = $studentProgramContext->participantLabel(auth()->user());
        $studentActiveBatch = $activeProgramBatch ?? $studentProgramContext->activeBatch(auth()->user());
        $progressPercent = $summary['total_sessions'] > 0
            ? (int) round(($summary['completed_sessions'] / $summary['total_sessions']) * 100)
            : 0;
        $greeting = match (true) {
            now()->hour < 11 => 'Selamat pagi',
            now()->hour < 15 => 'Selamat siang',
            now()->hour < 18 => 'Selamat sore',
            default => 'Selamat malam',
        };
    @endphp

    <div class="student-dashboard-page">
        <section class="student-dashboard-hero" id="profil" aria-labelledby="student-dashboard-title">
            <div class="student-dashboard-hero-copy">
                <div class="d-flex align-items-center gap-3 mb-4">
                    <a href="{{ route('account.profile-photo.edit') }}" class="text-decoration-none">
                        <x-ui.avatar :name="$student->name" :user="$student" size="xl" status="online" />
                    </a>
                    <div>
                        <p class="student-dashboard-kicker mb-1">Dashboard pribadi {{ strtolower($studentParticipantLabel) }}</p>
                        <p class="mb-0 text-white-50">{{ $studentActiveBatch?->program?->name ?? 'Program aktif' }} · {{ $schoolClass?->academicYear?->name ?? 'Periode belum tersedia' }}</p>
                    </div>
                </div>

                <p class="student-dashboard-greeting">{{ $greeting }},</p>
                <h1 id="student-dashboard-title">{{ $profile?->nickname ?: $student->name }}</h1>
                <p class="student-dashboard-intro">
                    {{ $schoolClass?->name ?? 'Belum terhubung ke kelas' }}
                    <span aria-hidden="true">·</span>
                    {{ $profile?->student_number ?? $student->email }}
                </p>

                <div class="d-flex flex-wrap gap-2 mt-4">
                    <x-ui.badge variant="success" icon="bi-check-circle">{{ $studentParticipantLabel }} {{ ucfirst($student->status->value) }}</x-ui.badge>
                    @foreach (array_slice($interests, 0, 3) as $interest)
                        <x-ui.badge variant="premium">{{ ucfirst($interest) }}</x-ui.badge>
                    @endforeach
                </div>
            </div>

            <div class="student-progress-panel">
                <div
                    class="student-progress-ring"
                    style="--student-progress: {{ $progressPercent }}"
                    role="progressbar"
                    aria-label="Progress belajar program"
                    aria-valuemin="0"
                    aria-valuemax="100"
                    aria-valuenow="{{ $progressPercent }}"
                >
                    <span><strong>{{ $progressPercent }}%</strong><small>selesai</small></span>
                </div>
                <div>
                    <p class="student-dashboard-kicker mb-2">Perjalanan belajar</p>
                    <h2>{{ $summary['completed_sessions'] }} dari {{ $summary['total_sessions'] }} pertemuan</h2>
                    <p>Progress akan bergerak saat materi pembelajaran mulai dipublikasikan.</p>
                </div>
            </div>
        </section>

        <section class="student-summary-grid" aria-label="Ringkasan belajar">
            @foreach ([
                ['Pertemuan selesai', $summary['completed_sessions'], 'bi-journal-check', 'teal'],
                ['Tugas aktif', $summary['active_assignments'], 'bi-clipboard-check', 'navy'],
                ['Perlu revisi', $summary['revisions'], 'bi-arrow-repeat', 'orange'],
                ['Rata-rata nilai', $summary['average_grade'], 'bi-award', 'cyan'],
                ['Kehadiran', $summary['attendance_rate'].'%', 'bi-calendar2-check', 'teal'],
                ['Karya portofolio', $summary['portfolio_count'], 'bi-collection', 'navy'],
            ] as [$label, $value, $icon, $tone])
                <article class="student-summary-card student-summary-card-{{ $tone }}">
                    <span><i class="bi {{ $icon }}" aria-hidden="true"></i></span>
                    <div><strong>{{ $value }}</strong><small>{{ $label }}</small></div>
                </article>
            @endforeach
        </section>

        <div class="row g-4 align-items-start">
            <div class="col-xl-8">
                <div class="d-grid gap-4">
                    <section class="student-dashboard-section" id="lanjut-belajar" aria-labelledby="continue-learning-title">
                        <div class="student-section-heading">
                            <div><p class="skuad-eyebrow">Fokus berikutnya</p><h2 id="continue-learning-title">Lanjutkan belajar</h2></div>
                            <span class="student-section-index">01</span>
                        </div>

                        @if ($continueLearning)
                            @php($continueProgress = $continueLearning->progressRecords->first()?->progress_percent ?? 0)
                            <a class="student-continue-card" href="{{ route('student.learning.show', $continueLearning) }}">
                                <span class="student-continue-number">{{ str_pad($continueLearning->session_number, 2, '0', STR_PAD_LEFT) }}</span>
                                <div>
                                    <small>Modul {{ $continueLearning->module->module_number }} · {{ $continueLearning->module->title }}</small>
                                    <h3>{{ $continueLearning->title }}</h3>
                                    <p>{{ $continueLearning->materials->count() }} materi · {{ $continueLearning->duration_minutes }} menit</p>
                                </div>
                                <div class="student-continue-progress"><strong>{{ $continueProgress }}%</strong><span><i style="width: {{ $continueProgress }}%"></i></span></div>
                                <i class="bi bi-arrow-up-right"></i>
                            </a>
                        @else
                            <x-ui.empty-state
                                class="student-dashboard-empty"
                                title="Materi pertama sedang disiapkan"
                                description="Saat pembina mempublikasikan pertemuan, kamu dapat melanjutkan belajar langsung dari sini."
                                icon="bi-journal-richtext"
                            />
                        @endif
                    </section>

                    <section class="student-dashboard-section" id="tugas" aria-labelledby="assignments-title">
                        <div class="student-section-heading">
                            <div><p class="skuad-eyebrow">Ruang kerja</p><h2 id="assignments-title">Tugas dan revisi</h2></div>
                            <span class="student-section-index">02</span>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="student-subsection h-100">
                                    <div class="student-subsection-title"><span class="bg-info-subtle text-info"><i class="bi bi-clipboard2"></i></span><h3>Tugas aktif</h3></div>
                                    @forelse ($upcomingAssignments as $assignment)
                                        <a class="student-dashboard-task" href="{{ route('student.assignments.show', $assignment) }}">
                                            <span><strong>{{ $assignment->title }}</strong><small>{{ $assignment->due_at->diffForHumans() }}</small></span><i class="bi bi-chevron-right"></i>
                                        </a>
                                    @empty
                                        <div class="student-compact-empty"><strong>Belum ada tugas aktif</strong><p>Tugas baru akan tampil lengkap dengan tenggatnya.</p></div>
                                    @endforelse
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="student-subsection h-100">
                                    <div class="student-subsection-title"><span class="bg-warning-subtle text-warning"><i class="bi bi-arrow-repeat"></i></span><h3>Perlu revisi</h3></div>
                                    @forelse ($revisionAssignments as $submission)
                                        <a class="student-dashboard-task" href="{{ route('student.assignments.show', $submission->assignment) }}">
                                            <span><strong>{{ $submission->assignment->title }}</strong><small>{{ \Illuminate\Support\Str::limit($submission->revision_note, 65) }}</small></span><i class="bi bi-arrow-repeat"></i>
                                        </a>
                                    @empty
                                        <div class="student-compact-empty"><strong>Tidak ada revisi</strong><p>Feedback pembina yang perlu ditindaklanjuti akan muncul di sini.</p></div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="student-dashboard-section" aria-labelledby="latest-grades-title">
                        <div class="student-section-heading">
                            <div><p class="skuad-eyebrow">Capaian</p><h2 id="latest-grades-title">Nilai terbaru</h2></div>
                            <span class="student-section-index">03</span>
                        </div>
                        @if ($latestMonthlyAssessment)
                            <a class="student-dashboard-task mb-3" href="{{ route('student.grades.monthly.show', $latestMonthlyAssessment) }}">
                                <span>
                                    <strong>Asesmen bulanan · {{ $latestMonthlyAssessment->period_label }}</strong>
                                    <small>
                                        Level {{ $latestMonthlyAssessment->achievement_level }}
                                        · {{ \App\Models\MonthlyStudentAssessment::achievementLabel($latestMonthlyAssessment->achievement_level) }}
                                    </small>
                                    @if($latestMonthlyAssessment->improvement_targets)
                                        <small>{{ \Illuminate\Support\Str::limit($latestMonthlyAssessment->improvement_targets, 80) }}</small>
                                    @endif
                                </span>
                                <b>{{ number_format((float) $latestMonthlyAssessment->final_score, 0) }}</b>
                            </a>
                        @endif

                        @if ($latestGroupAssessment)
                            <a class="student-dashboard-task mb-3" href="{{ route('student.grades.group-projects.show', $latestGroupAssessment) }}">
                                <span>
                                    <strong>Proyek kelompok · {{ $latestGroupAssessment->groupProject->title }}</strong>
                                    <small>
                                        {{ $latestGroupAssessment->groupProject->projectGroup->name }}
                                        · Level {{ $latestGroupAssessment->achievement_level }}
                                    </small>
                                    @if($latestGroupAssessment->feedback)
                                        <small>{{ \Illuminate\Support\Str::limit($latestGroupAssessment->feedback, 80) }}</small>
                                    @endif
                                </span>
                                <b>{{ number_format((float) $latestGroupAssessment->final_score, 0) }}</b>
                            </a>
                        @endif

                        @forelse ($latestGrades as $grade)
                            <a class="student-dashboard-task" href="{{ route('student.grades.show', $grade) }}">
                                <span><strong>{{ $grade->submission->assignment->title }}</strong><small>Level {{ $grade->achievement_level }} · {{ $grade->published_at->diffForHumans() }}</small></span><b>{{ number_format((float) $grade->total_score, 0) }}</b>
                            </a>
                        @empty
                            @unless($latestMonthlyAssessment || $latestGroupAssessment)
                                <div class="student-compact-empty student-compact-empty-horizontal">
                                    <span><i class="bi bi-award"></i></span>
                                    <div><strong>Belum ada nilai yang dipublikasikan</strong><p>Nilai dan feedback hanya tampil setelah ditinjau serta dipublikasikan pembina.</p></div>
                                </div>
                            @endunless
                        @endforelse
                    </section>
                </div>
            </div>

            <div class="col-xl-4">
                <div class="d-grid gap-4">
                    <section class="student-dashboard-section" aria-labelledby="attendance-title">
                        <div class="student-section-heading">
                            <div><p class="skuad-eyebrow">Konsistensi</p><h2 id="attendance-title">Kehadiran saya</h2></div>
                            <a href="{{ route('student.attendance.index') }}" class="btn btn-sm btn-outline-primary">Lihat riwayat</a>
                        </div>
                        <div class="student-attendance-zero">
                            <strong>{{ $summary['attendance_rate'] }}%</strong>
                            <p>{{ $attendanceHistory->isEmpty() ? 'Rekap akan tersedia setelah sesi presensi pertama.' : $attendanceHistory->count().' catatan kehadiran terbaru tersedia.' }}</p>
                        </div>
                    </section>

                    <section class="student-dashboard-section" id="portofolio" aria-labelledby="portfolio-title">
                        <div class="student-section-heading">
                            <div><p class="skuad-eyebrow">Karya pilihan</p><h2 id="portfolio-title">Portofolio</h2></div>
                        </div>
                        @forelse ($featuredPortfolio as $portfolio)
                            <a class="student-dashboard-task" href="{{ route('student.portfolio.show', $portfolio) }}">
                                <span><strong>{{ $portfolio->title }}</strong><small>{{ $portfolio->workTypeLabel() }} · {{ $portfolio->visibility->label() }}</small></span>
                                <i class="bi bi-arrow-up-right"></i>
                            </a>
                        @empty
                            <div class="student-compact-empty"><strong>Ruang karya masih kosong</strong><p>Karya yang disetujui dan dipilih akan tampil di etalase ini.</p></div>
                        @endforelse
                        <a class="btn btn-outline-primary w-100 mt-3" href="{{ route('student.portfolio.index') }}">Kelola semua karya</a>
                    </section>

                    <section class="student-dashboard-section" aria-labelledby="announcements-title">
                        <div class="student-section-heading">
                            <div><p class="skuad-eyebrow">Informasi</p><h2 id="announcements-title">Pengumuman</h2></div>
                        </div>
                        @forelse ($announcements as $announcement)
                            <a class="student-dashboard-task" href="{{ route('interactions.announcements.show',$announcement) }}"><span><strong>{{ $announcement->title }}</strong><small>{{ $announcement->priority->label() }} · {{ $announcement->published_at?->diffForHumans() }}</small></span><i class="bi bi-chevron-right"></i></a>
                        @empty
                            <div class="student-compact-empty"><strong>Belum ada pengumuman</strong><p>Informasi terbaru dari pembina akan muncul di sini.</p></div>
                        @endforelse
                        <a class="btn btn-outline-primary w-100 mt-3" href="{{ route('interactions.announcements.index') }}">Lihat semua pengumuman</a>
                    </section>
                </div>
            </div>
        </div>
    </div>
@endsection
