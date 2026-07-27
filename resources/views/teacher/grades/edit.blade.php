@extends('layouts.dashboard')

@section('title', 'Nilai '.$submission->student->name)
@section('breadcrumb', 'Grading')

@section('content')
    @php($version = $submission->versions->firstWhere('version_number', $submission->current_version_number))
    <div class="grading-page">
        <div class="grading-hero">
            <div><p>{{ $submission->assignment->title }}</p><h1>{{ $submission->student->name }}</h1><span>{{ $submission->assignment->rubric->name }} · Versi {{ $submission->current_version_number }}</span></div>
            <div class="grade-live"><strong data-grade-total>{{ number_format((float) ($grade?->total_score ?? 0), 2) }}</strong><small>/ 100</small></div>
        </div>

        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

        <form method="POST" action="{{ route('teacher.grades.update', $submission) }}" data-grading-form>
            @csrf
            @method('PUT')
            <div class="split-grading">
                <aside class="submission-preview">
                    <h2>Submission terbaru</h2>
                    @if($version?->answers?->isNotEmpty())
                        <div class="d-grid gap-3 mb-3">
                            @foreach($version->answers->sortBy('question.sort_order') as $answer)
                                <section class="border rounded-4 p-3">
                                    <small>Pertanyaan {{ $answer->question?->sort_order }}</small>
                                    <h3 class="h6">{{ $answer->question?->prompt }}</h3>
                                    @if($answer->answer_url)<a href="{{ $answer->answer_url }}" target="_blank" rel="noopener">{{ $answer->answer_url }}</a>@endif
                                    @if($answer->answer_text)<p>{!! nl2br(e($answer->answer_text)) !!}</p>@endif
                                </section>
                            @endforeach
                        </div>
                    @endif
                    @if($version?->text_content)<p>{!! nl2br(e($version->text_content)) !!}</p>@endif
                    @if($version?->video_url)<a href="{{ $version->video_url }}" target="_blank" rel="noopener">Buka video</a>@endif
                    @if($version?->external_url)<a href="{{ $version->external_url }}" target="_blank" rel="noopener">Buka karya</a>@endif
                    <div class="submission-files">@foreach($version?->files ?? [] as $file)<a href="{{ route('submission-files.download', $file) }}">{{ $file->original_name }}</a>@endforeach</div>
                </aside>
                <main class="score-sheet">
                    @foreach($submission->assignment->rubric->criteria as $i => $criterion)
                        @php($existing = $submission->scores->firstWhere('rubric_criterion_id', $criterion->id))
                        <article data-score-criterion data-weight="{{ $criterion->weight }}">
                            <header><div><h2>{{ $criterion->name }}</h2><p>{{ $criterion->description }}</p></div><strong>{{ $criterion->weight }}%</strong></header>
                            <input type="hidden" name="scores[{{ $i }}][criterion_id]" value="{{ $criterion->id }}">
                            <div class="level-options">
                                @foreach($criterion->levels as $level)
                                    <label><input type="radio" name="scores[{{ $i }}][level]" value="{{ $level->level }}" @checked(old("scores.$i.level", $existing?->level) === $level->level) required><span><b>{{ $level->level }}</b><strong>{{ $level->label }}</strong><small>{{ $level->description }}</small></span></label>
                                @endforeach
                            </div>
                            <textarea class="form-control mt-3" name="scores[{{ $i }}][teacher_note]" placeholder="Catatan kriteria">{{ old("scores.$i.teacher_note", $existing?->teacher_note) }}</textarea>
                        </article>
                    @endforeach
                    <div class="row g-3">
                        <div class="col-12"><label class="form-label">Feedback untuk siswa</label><textarea class="form-control" name="feedback" rows="5">{{ old('feedback', $grade?->feedback) }}</textarea></div>
                        <div class="col-12"><label class="form-label">Catatan privat guru</label><textarea class="form-control" name="private_note" rows="3">{{ old('private_note', $grade?->private_note) }}</textarea></div>
                        <div class="col-md-4"><label class="form-label">Remedial</label><select class="form-select" name="remedial_status">@foreach($remedialStatuses as $status)<option value="{{ $status->value }}" @selected(old('remedial_status', $grade?->remedial_status?->value ?? 'none') === $status->value)>{{ $status->label() }}</option>@endforeach</select></div>
                        <div class="col-md-8"><label class="form-label">Instruksi remedial</label><input class="form-control" name="remedial_note" value="{{ old('remedial_note', $grade?->remedial_note) }}"></div>
                        <div class="col-md-6"><label class="form-label">Tenggat remedial</label><input class="form-control" type="datetime-local" name="remedial_due_at" value="{{ old('remedial_due_at', $grade?->remedial_due_at?->format('Y-m-d\TH:i')) }}"></div>
                        <div class="col-md-6"><label class="form-label">Catatan revisi</label><input class="form-control" name="revision_note" value="{{ old('revision_note') }}"></div>
                    </div>
                </main>
            </div>
            <div class="grading-sticky"><button class="btn btn-outline-primary" name="action" value="draft">Simpan draf</button><button class="btn btn-warning" name="action" value="revision">Minta revisi</button><button class="btn btn-success" name="action" value="publish">Publikasikan nilai</button></div>
        </form>
        @if($grade?->remedial_status === \App\Enums\RemedialStatus::Submitted)
            <form method="POST" action="{{ route('teacher.grades.remedial.complete', $grade) }}">@csrf @method('PATCH')<div class="alert alert-info"><strong>Jawaban remedial:</strong><p>{{ $grade->remedial_response }}</p><button class="btn btn-success">Tandai selesai</button></div></form>
        @endif
    </div>
@endsection
