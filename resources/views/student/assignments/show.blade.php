@extends('layouts.dashboard')

@section('title', $assignment->title)
@section('breadcrumb', 'Kerjakan tugas')

@section('content')
    @php
        $current = $submission?->versions?->firstWhere('version_number', $submission->current_version_number);
        $editable = ! $submission || in_array($submission->status, [\App\Enums\SubmissionStatus::Draft, \App\Enums\SubmissionStatus::RevisionRequested], true);
        $savedAnswers = $current?->answers?->keyBy('assignment_question_id') ?? collect();
    @endphp

    <div class="assignment-page">
        <div class="assignment-hero">
            <div>
                <p>PERTEMUAN {{ $assignment->learningSession->session_number }} · {{ $assignment->type->label() }}</p>
                <h1>{{ $assignment->title }}</h1>
                <span>Tenggat {{ $assignment->due_at->translatedFormat('d F Y H:i') }}</span>
            </div>
            <strong>{{ $submission?->status->label() ?? 'Belum mulai' }}</strong>
        </div>

        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
        @if($submission?->revision_note)<div class="alert alert-warning"><strong>Catatan revisi:</strong> {{ $submission->revision_note }}</div>@endif

        <div class="student-assignment-grid">
            <aside class="assignment-paper">
                <h2>Instruksi</h2>
                <p>{!! nl2br(e($assignment->instructions)) !!}</p>
                <div class="assignment-rules">
                    <span>{{ $assignment->questions->count() }} pertanyaan</span>
                    <span>Maks. {{ $assignment->max_files }} file</span>
                    <span>{{ round($assignment->max_file_size_kb / 1024, 1) }} MB/file</span>
                    <span>{{ $assignment->max_revisions }} revisi</span>
                </div>
            </aside>

            <main class="assignment-form">
                <form method="POST" enctype="multipart/form-data" action="{{ route('student.assignments.save', $assignment) }}">
                    @csrf

                    @if($assignment->questions->isNotEmpty())
                        <section class="d-grid gap-3 mb-4">
                            <h2 class="h5">Jawab pertanyaan tugas</h2>
                            @foreach($assignment->questions as $index => $question)
                                @php($answer = $savedAnswers->get($question->id))
                                <article class="border rounded-4 p-3">
                                    <label class="form-label fw-bold" for="answer-{{ $question->id }}">
                                        {{ $index + 1 }}. {{ $question->prompt }}
                                        @if($question->is_required)<span class="text-danger">*</span>@endif
                                    </label>
                                    @if($question->help_text)<p class="text-secondary small mb-2">{{ $question->help_text }}</p>@endif
                                    <input type="hidden" name="answers[{{ $index }}][question_id]" value="{{ $question->id }}">
                                    @if($question->answer_type === \App\Enums\AssignmentQuestionType::Url)
                                        <input class="form-control" id="answer-{{ $question->id }}" name="answers[{{ $index }}][answer_url]" value="{{ old("answers.$index.answer_url", $answer?->answer_url) }}" placeholder="https://..." @disabled(!$editable)>
                                    @elseif($question->answer_type === \App\Enums\AssignmentQuestionType::MultipleChoice)
                                        <div class="d-grid gap-2">
                                            @foreach($question->options ?? [] as $optionIndex => $option)
                                                <label class="form-check border rounded-3 px-3 py-2">
                                                    <input class="form-check-input ms-0 me-2" type="radio" name="answers[{{ $index }}][answer_text]" value="{{ $option }}" @checked(old("answers.$index.answer_text", $answer?->answer_text) === $option) @disabled(!$editable)>
                                                    <span class="form-check-label">{{ $option }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    @elseif($question->answer_type === \App\Enums\AssignmentQuestionType::ShortText)
                                        <input class="form-control" id="answer-{{ $question->id }}" name="answers[{{ $index }}][answer_text]" value="{{ old("answers.$index.answer_text", $answer?->answer_text) }}" @disabled(!$editable)>
                                    @else
                                        <textarea class="form-control" id="answer-{{ $question->id }}" name="answers[{{ $index }}][answer_text]" rows="5" @disabled(!$editable)>{{ old("answers.$index.answer_text", $answer?->answer_text) }}</textarea>
                                    @endif
                                </article>
                            @endforeach
                        </section>
                    @endif

                    @if($assignment->type->acceptsText())
                        <label class="form-label">Jawaban / refleksi tambahan</label>
                        <textarea class="form-control mb-3" name="text_content" rows="8" @disabled(!$editable)>{{ old('text_content', $current?->text_content) }}</textarea>
                    @endif
                    @if($assignment->type === \App\Enums\AssignmentType::VideoLink)
                        <label class="form-label">URL video</label>
                        <input class="form-control mb-3" name="video_url" value="{{ old('video_url', $current?->video_url) }}" @disabled(!$editable)>
                    @endif
                    @if($assignment->type === \App\Enums\AssignmentType::ExternalLink || $assignment->type === \App\Enums\AssignmentType::Mixed)
                        <label class="form-label">URL karya</label>
                        <input class="form-control mb-3" name="external_url" value="{{ old('external_url', $current?->external_url) }}" @disabled(!$editable)>
                    @endif
                    @if($assignment->type->acceptsFiles())
                        <label class="form-label">Unggah file</label>
                        <input class="form-control mb-3" type="file" name="files[]" multiple @disabled(!$editable)>
                    @endif
                    @if($current?->files?->isNotEmpty())
                        <div class="submission-files mb-3">
                            @foreach($current->files as $file)
                                <label><a href="{{ route('submission-files.download', $file) }}">{{ $file->original_name }}</a>@if($editable)<input type="checkbox" name="remove_files[]" value="{{ $file->id }}"> Hapus @endif</label>
                            @endforeach
                        </div>
                    @endif
                    <label class="form-label">Catatan untuk pembina</label>
                    <textarea class="form-control" name="student_note" rows="3" @disabled(!$editable)>{{ old('student_note', $current?->student_note) }}</textarea>
                    @if($editable)
                        <div class="assignment-sticky"><button class="btn btn-outline-primary" name="action" value="draft">Simpan draf</button><button class="btn btn-primary" name="action" value="submit">Kirim tugas</button></div>
                    @else
                        <div class="alert alert-info mt-3">Submission terkunci selama menunggu review pembina.</div>
                    @endif
                </form>
            </main>
        </div>

        @if($submission)
            <section class="version-history">
                <h2>Histori versi</h2>
                @foreach($submission->versions->sortByDesc('version_number') as $version)
                    <div><strong>Versi {{ $version->version_number }}</strong><span>{{ $version->submitted_at?->translatedFormat('d M Y H:i') ?? 'Draf aktif' }}</span><small>{{ $version->files->count() }} file · {{ $version->answers->count() }} jawaban</small></div>
                @endforeach
            </section>
        @endif
    </div>
@endsection
