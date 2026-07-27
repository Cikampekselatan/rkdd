@extends('layouts.dashboard')

@section('title', 'Submission '.$submission->student->name)
@section('breadcrumb', 'Review submission')

@section('content')
    <div class="assignment-page">
        <div class="assignment-hero">
            <div><p>{{ $submission->assignment->title }}</p><h1>{{ $submission->student->name }}</h1><span>{{ $submission->status->label() }} · {{ $submission->revision_count }} revisi</span></div>
            <strong>Versi {{ $submission->current_version_number }}</strong>
        </div>

        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
        @if($submission->assignment->rubric_id)
            <a class="btn btn-primary" href="{{ route('teacher.grades.edit', $submission) }}"><i class="bi bi-ui-checks-grid"></i> Buka grading rubrik</a>
        @else
            <div class="alert alert-warning">Tugas belum memiliki rubrik. Pilih rubrik pada pengaturan tugas sebelum menilai.</div>
        @endif

        <div class="version-list">
            @foreach($submission->versions->sortByDesc('version_number') as $version)
                <article>
                    <header><h2>Versi {{ $version->version_number }}</h2><span>{{ $version->submitted_at?->translatedFormat('d M Y H:i') ?? 'Draf' }}</span></header>
                    @if($version->answers->isNotEmpty())
                        <div class="d-grid gap-3 mb-3">
                            @foreach($version->answers->sortBy('question.sort_order') as $answer)
                                <section class="border rounded-4 p-3">
                                    <small class="text-secondary">Pertanyaan {{ $answer->question?->sort_order }}</small>
                                    <h3 class="h6">{{ $answer->question?->prompt }}</h3>
                                    @if($answer->answer_url)<a href="{{ $answer->answer_url }}" target="_blank" rel="noopener">{{ $answer->answer_url }}</a>@endif
                                    @if($answer->answer_text)<p>{!! nl2br(e($answer->answer_text)) !!}</p>@endif
                                </section>
                            @endforeach
                        </div>
                    @endif
                    @if($version->text_content)<p>{!! nl2br(e($version->text_content)) !!}</p>@endif
                    @if($version->video_url)<a href="{{ $version->video_url }}" target="_blank">Buka video</a>@endif
                    @if($version->external_url)<a href="{{ $version->external_url }}" target="_blank">Buka karya</a>@endif
                    <div class="submission-files">@foreach($version->files as $file)<a href="{{ route('submission-files.download', $file) }}">{{ $file->original_name }}</a>@endforeach</div>
                </article>
            @endforeach
        </div>

        @can('review', $submission)
            <div class="review-bar">
                <form method="POST" action="{{ route('teacher.submissions.review', $submission) }}">@csrf @method('PATCH')<button class="btn btn-outline-primary">Mulai review</button></form>
                <form method="POST" action="{{ route('teacher.submissions.revision', $submission) }}">@csrf @method('PATCH')<textarea class="form-control" name="revision_note" required placeholder="Catatan revisi"></textarea><button class="btn btn-warning">Minta revisi</button></form>
            </div>
        @endcan
    </div>
@endsection
