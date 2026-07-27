@extends('layouts.dashboard')

@section('content')
    <div class="grading-page">
        <div class="student-grade-hero">
            <div>
                <p>Asesmen bulanan · {{ $assessment->period_label }}</p>
                <h1>{{ number_format((float) $assessment->final_score, 2) }}</h1>
                <span>
                    Level {{ $assessment->achievement_level }}
                    · {{ \App\Models\MonthlyStudentAssessment::achievementLabel($assessment->achievement_level) }}
                    · Semester {{ $assessment->semester }}
                </span>
            </div>
            <i class="bi bi-clipboard2-check"></i>
        </div>

        <div class="row g-4 align-items-start">
            <div class="col-lg-7">
                <section class="student-score-list">
                    @foreach($components as $field => $component)
                        <article>
                            <div>
                                <h2>{{ $component['label'] }}</h2>
                                <p>Bobot {{ $component['weight'] }}% dari asesmen bulanan.</p>
                            </div>
                            <strong>{{ number_format((float) $assessment->{$field}, 0) }}</strong>
                            <span>{{ number_format(((float) $assessment->{$field}) * $component['weight'] / 100, 2) }} poin</span>
                        </article>
                    @endforeach
                </section>
            </div>

            <div class="col-lg-5">
                <section class="grade-feedback mb-4">
                    <h2>Ringkasan produk dan bukti</h2>
                    <p>{!! nl2br(e($assessment->product_summary ?: 'Belum ada ringkasan produk.')) !!}</p>
                    @if($assessment->evidence_url)
                        <a class="btn btn-outline-primary" href="{{ $assessment->evidence_url }}" target="_blank" rel="noopener">
                            Buka bukti karya
                        </a>
                    @endif
                </section>

                <section class="grade-feedback mb-4">
                    <h2>Kekuatan bulan ini</h2>
                    <p>{!! nl2br(e($assessment->strengths ?: 'Belum ada catatan kekuatan.')) !!}</p>
                </section>

                <section class="grade-feedback mb-4">
                    <h2>Target peningkatan</h2>
                    <p>{!! nl2br(e($assessment->improvement_targets ?: 'Belum ada target peningkatan.')) !!}</p>
                </section>

                @if($assessment->remedial_plan || $assessment->enrichment_plan)
                    <section class="remedial-card">
                        <h2>Rencana tindak lanjut</h2>
                        @if($assessment->remedial_plan)
                            <h3 class="h6">Remedial</h3>
                            <p>{!! nl2br(e($assessment->remedial_plan)) !!}</p>
                        @endif
                        @if($assessment->enrichment_plan)
                            <h3 class="h6">Pengayaan</h3>
                            <p>{!! nl2br(e($assessment->enrichment_plan)) !!}</p>
                        @endif
                    </section>
                @endif
            </div>
        </div>

        <a class="btn btn-outline-secondary mt-4" href="{{ route('student.grades.index') }}">Kembali ke nilai saya</a>
    </div>
@endsection
