@extends('layouts.dashboard')

@section('content')
    <div class="grading-page">
        <x-ui.page-header
            eyebrow="Capaian"
            title="Nilai saya"
            description="Nilai tugas dan asesmen bulanan hanya tampil setelah dipublikasikan pembina."
        />

        <section class="mb-5">
            <div class="d-flex align-items-end justify-content-between gap-3 mb-3">
                <div>
                    <p class="skuad-eyebrow mb-1">Asesmen periodik</p>
                    <h2 class="h4 mb-0">Asesmen bulanan</h2>
                </div>
                <span class="badge text-bg-light">{{ $monthlyAssessments->count() }} laporan</span>
            </div>

            <div class="grade-grid">
                @forelse($monthlyAssessments as $assessment)
                    <a href="{{ route('student.grades.monthly.show', $assessment) }}">
                        <strong>{{ number_format((float) $assessment->final_score, 0) }}</strong>
                        <div>
                            <h2>{{ $assessment->period_label }}</h2>
                            <p>
                                Semester {{ $assessment->semester }}
                                · Level {{ $assessment->achievement_level }}
                                · {{ \App\Models\MonthlyStudentAssessment::achievementLabel($assessment->achievement_level) }}
                            </p>
                            @if($assessment->strengths)
                                <small>{{ \Illuminate\Support\Str::limit($assessment->strengths, 90) }}</small>
                            @endif
                        </div>
                        <i class="bi bi-chevron-right"></i>
                    </a>
                @empty
                    <x-ui.empty-state
                        title="Belum ada asesmen bulanan"
                        description="Laporan bulanan akan muncul setelah guru mempublikasikan penilaian semester."
                        icon="bi-clipboard2-pulse"
                    />
                @endforelse
            </div>
        </section>

        <section class="mb-5">
            <div class="d-flex align-items-end justify-content-between gap-3 mb-3">
                <div>
                    <p class="skuad-eyebrow mb-1">Kolaborasi</p>
                    <h2 class="h4 mb-0">Nilai proyek kelompok</h2>
                </div>
                <span class="badge text-bg-light">{{ $groupAssessments->count() }} nilai</span>
            </div>

            <div class="grade-grid">
                @forelse($groupAssessments as $assessment)
                    <a href="{{ route('student.grades.group-projects.show', $assessment) }}">
                        <strong>{{ number_format((float) $assessment->final_score, 0) }}</strong>
                        <div>
                            <h2>{{ $assessment->groupProject->title }}</h2>
                            <p>
                                {{ $assessment->groupProject->projectGroup->name }}
                                · Level {{ $assessment->achievement_level }}
                                · {{ \App\Models\GroupProjectAssessment::achievementLabel($assessment->achievement_level) }}
                            </p>
                            @if($assessment->feedback)
                                <small>{{ \Illuminate\Support\Str::limit($assessment->feedback, 90) }}</small>
                            @endif
                        </div>
                        <i class="bi bi-chevron-right"></i>
                    </a>
                @empty
                    <x-ui.empty-state
                        title="Belum ada nilai proyek kelompok"
                        description="Nilai proyek kelompok akan masuk otomatis setelah guru/coach mempublikasikannya."
                        icon="bi-people"
                    />
                @endforelse
            </div>
        </section>

        <section>
            <div class="d-flex align-items-end justify-content-between gap-3 mb-3">
                <div>
                    <p class="skuad-eyebrow mb-1">Tugas dan karya</p>
                    <h2 class="h4 mb-0">Nilai tugas</h2>
                </div>
            </div>

            <div class="grade-grid">
                @forelse($grades as $grade)
                    <a href="{{ route('student.grades.show', $grade) }}">
                        <strong>{{ number_format((float) $grade->total_score, 2) }}</strong>
                        <div>
                            <h2>{{ $grade->submission->assignment->title }}</h2>
                            <p>Level {{ $grade->achievement_level }} · {{ $grade->published_at->translatedFormat('d M Y') }}</p>
                        </div>
                        <i class="bi bi-chevron-right"></i>
                    </a>
                @empty
                    <x-ui.empty-state
                        title="Belum ada nilai tugas"
                        description="Nilai dan feedback tugas akan muncul setelah pembina mempublikasikannya."
                        icon="bi-award"
                    />
                @endforelse
            </div>

            {{ $grades->links() }}
        </section>
    </div>
@endsection
