@extends('layouts.dashboard')

@section('content')
    <div class="grading-page">
        <div class="student-grade-hero">
            <div>
                <p>Nilai proyek kelompok · {{ $assessment->groupProject->projectGroup->name }}</p>
                <h1>{{ number_format((float) $assessment->final_score, 2) }}</h1>
                <span>
                    Level {{ $assessment->achievement_level }}
                    · {{ \App\Models\GroupProjectAssessment::achievementLabel($assessment->achievement_level) }}
                </span>
            </div>
            <i class="bi bi-people"></i>
        </div>

        <div class="row g-4">
            <div class="col-lg-7">
                <section class="grade-feedback">
                    <h2>{{ $assessment->groupProject->title }}</h2>
                    <p>{!! nl2br(e($assessment->groupProject->description ?: 'Belum ada deskripsi proyek.')) !!}</p>
                    @if($assessment->groupProject->evidence_url)
                        <a class="btn btn-outline-primary" href="{{ $assessment->groupProject->evidence_url }}" target="_blank" rel="noopener">Buka bukti karya</a>
                    @endif
                </section>

                <section class="grade-feedback mt-4">
                    <h2>Feedback guru/coach</h2>
                    <p>{!! nl2br(e($assessment->feedback ?: 'Belum ada feedback tertulis.')) !!}</p>
                </section>
            </div>

            <div class="col-lg-5">
                <section class="card border-0 shadow-sm p-4">
                    <h2 class="h5">Anggota kelompok</h2>
                    <div class="list-group list-group-flush">
                        @foreach($assessment->groupProject->projectGroup->members->where('is_active', true) as $member)
                            <div class="list-group-item px-0">{{ $member->student->name }}</div>
                        @endforeach
                    </div>
                </section>
            </div>
        </div>

        <a class="btn btn-outline-secondary mt-4" href="{{ route('student.grades.index') }}">Kembali ke nilai saya</a>
    </div>
@endsection
