@extends('layouts.dashboard')

@section('title', ($assignment->exists ? 'Edit' : 'Buat').' Tugas')
@section('breadcrumb', 'Form tugas')

@section('content')
    <div class="assignment-page">
        <x-ui.page-header eyebrow="Konfigurasi tugas" :title="$assignment->exists ? 'Perbarui tugas' : 'Buat tugas baru'" description="Tentukan instruksi, pertanyaan siswa, rubrik, file, dan batas revisi." />

        @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

        @php
            $questionRows = collect(old('questions', $assignment->exists ? $assignment->questions->map(fn ($q) => [
                'prompt' => $q->prompt,
                'help_text' => $q->help_text,
                'answer_type' => $q->answer_type->value,
                'options_text' => implode("\n", $q->options ?? []),
                'is_required' => $q->is_required,
            ])->all() : []));
            if ($questionRows->isEmpty()) {
                $questionRows = collect([['prompt' => '', 'help_text' => '', 'answer_type' => 'paragraph', 'options_text' => '', 'is_required' => true]]);
            }
        @endphp

        <form class="assignment-form" method="POST" action="{{ $assignment->exists ? route('teacher.assignments.update', $assignment) : route('teacher.assignments.store') }}">
            @csrf
            @if($assignment->exists)@method('PUT')@endif

            <div class="row g-4">
                <div class="col-md-4"><label class="form-label">Periode/Tahun Ajaran</label><select class="form-select" name="academic_year_id">@foreach($academicYears as $year)<option value="{{ $year->id }}" @selected(old('academic_year_id', $assignment->academic_year_id) == $year->id)>{{ $year->name }}</option>@endforeach</select></div>
                <div class="col-md-4"><label class="form-label">Kelompok/Angkatan</label><select class="form-select" name="class_id">@foreach($classes as $class)<option value="{{ $class->id }}" @selected(old('class_id', $assignment->class_id) == $class->id)>{{ $class->name }} · {{ $class->academicYear->name }}</option>@endforeach</select></div>
                <div class="col-md-4"><label class="form-label">Pertemuan</label><select class="form-select" name="learning_session_id">@foreach($learningSessions as $session)<option value="{{ $session->id }}" @selected(old('learning_session_id', $assignment->learning_session_id) == $session->id)>#{{ $session->session_number }} {{ $session->title }}</option>@endforeach</select></div>
                <div class="col-md-8"><label class="form-label">Judul tugas</label><input class="form-control" name="title" value="{{ old('title', $assignment->title) }}" required></div>
                <div class="col-md-4"><label class="form-label">Jenis jawaban tambahan</label><select class="form-select" name="type">@foreach($types as $type)<option value="{{ $type->value }}" @selected(old('type', $assignment->type?->value) === $type->value)>{{ $type->label() }}</option>@endforeach</select></div>
                <div class="col-12"><label class="form-label">Rubrik penilaian</label><select class="form-select" name="rubric_id"><option value="">Belum memakai rubrik</option>@foreach($rubrics as $rubric)<option value="{{ $rubric->id }}" @selected(old('rubric_id', $assignment->rubric_id) == $rubric->id)>{{ $rubric->name }} · {{ $rubric->criteria_sum_weight }}%</option>@endforeach</select></div>
                <div class="col-12"><label class="form-label">Instruksi umum</label><textarea class="form-control" name="instructions" rows="6" required>{{ old('instructions', $assignment->instructions) }}</textarea></div>

                <div class="col-12">
                    <section class="card border-0 shadow-sm p-4">
                        <div class="d-flex flex-wrap justify-content-between gap-3 align-items-start mb-3">
                            <div>
                                <h2 class="h5 mb-1">Pertanyaan tugas untuk siswa</h2>
                                <p class="text-secondary mb-0">Tambah, hapus, dan atur tipe pertanyaan. Untuk pilihan ganda, tulis opsi satu per baris.</p>
                            </div>
                            <button class="btn btn-outline-primary" type="button" data-assignment-question-add><i class="bi bi-plus-lg"></i> Tambah pertanyaan</button>
                        </div>

                        <div class="d-grid gap-3" data-assignment-question-list>
                            @foreach($questionRows as $index => $question)
                                <article class="border rounded-4 p-3" data-assignment-question-row>
                                    <div class="row g-3">
                                        <div class="col-md-7">
                                            <label class="form-label">Pertanyaan <span data-assignment-question-number>{{ $index + 1 }}</span></label>
                                            <input class="form-control" name="questions[{{ $index }}][prompt]" value="{{ $question['prompt'] ?? '' }}" placeholder="Contoh: Apa ide utama karya/proyekmu?">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Tipe jawaban</label>
                                            <select class="form-select" name="questions[{{ $index }}][answer_type]" data-assignment-question-type>
                                                @foreach($questionTypes as $type)
                                                    <option value="{{ $type->value }}" @selected(($question['answer_type'] ?? 'paragraph') === $type->value)>{{ $type->label() }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-2 d-flex align-items-end">
                                            <label class="form-check mb-2">
                                                <input type="hidden" name="questions[{{ $index }}][is_required]" value="0">
                                                <input class="form-check-input" type="checkbox" name="questions[{{ $index }}][is_required]" value="1" @checked((bool) ($question['is_required'] ?? true))>
                                                <span class="form-check-label">Wajib</span>
                                            </label>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Bantuan/petunjuk kecil</label>
                                            <input class="form-control" name="questions[{{ $index }}][help_text]" value="{{ $question['help_text'] ?? '' }}" placeholder="Opsional, misalnya: jawab 3-5 kalimat.">
                                        </div>
                                        <div class="col-12 {{ ($question['answer_type'] ?? 'paragraph') === 'multiple_choice' ? '' : 'd-none' }}" data-assignment-question-options>
                                            <label class="form-label">Opsi pilihan ganda</label>
                                            <textarea class="form-control" name="questions[{{ $index }}][options_text]" rows="4" placeholder="Satu opsi per baris. Contoh:&#10;Sangat setuju&#10;Setuju&#10;Kurang setuju">{{ $question['options_text'] ?? '' }}</textarea>
                                            <div class="form-text">Minimal 2 opsi. Siswa akan memilih salah satu.</div>
                                        </div>
                                        <div class="col-12 d-flex justify-content-end">
                                            <button class="btn btn-sm btn-outline-danger" type="button" data-assignment-question-remove><i class="bi bi-trash"></i> Hapus pertanyaan</button>
                                        </div>
                                    </div>
                                </article>
                            @endforeach
                        </div>

                        <template data-assignment-question-template>
                            <article class="border rounded-4 p-3" data-assignment-question-row>
                                <div class="row g-3">
                                    <div class="col-md-7"><label class="form-label">Pertanyaan <span data-assignment-question-number></span></label><input class="form-control" name="questions[__INDEX__][prompt]" placeholder="Contoh: Apa ide utama karya/proyekmu?"></div>
                                    <div class="col-md-3"><label class="form-label">Tipe jawaban</label><select class="form-select" name="questions[__INDEX__][answer_type]" data-assignment-question-type>@foreach($questionTypes as $type)<option value="{{ $type->value }}" @selected($type->value === 'paragraph')>{{ $type->label() }}</option>@endforeach</select></div>
                                    <div class="col-md-2 d-flex align-items-end"><label class="form-check mb-2"><input type="hidden" name="questions[__INDEX__][is_required]" value="0"><input class="form-check-input" type="checkbox" name="questions[__INDEX__][is_required]" value="1" checked><span class="form-check-label">Wajib</span></label></div>
                                    <div class="col-12"><label class="form-label">Bantuan/petunjuk kecil</label><input class="form-control" name="questions[__INDEX__][help_text]" placeholder="Opsional"></div>
                                    <div class="col-12 d-none" data-assignment-question-options><label class="form-label">Opsi pilihan ganda</label><textarea class="form-control" name="questions[__INDEX__][options_text]" rows="4" placeholder="Satu opsi per baris"></textarea><div class="form-text">Minimal 2 opsi. Siswa akan memilih salah satu.</div></div>
                                    <div class="col-12 d-flex justify-content-end"><button class="btn btn-sm btn-outline-danger" type="button" data-assignment-question-remove><i class="bi bi-trash"></i> Hapus pertanyaan</button></div>
                                </div>
                            </article>
                        </template>
                    </section>
                </div>

                <div class="col-md-6"><label class="form-label">Mulai tersedia</label><input class="form-control" type="datetime-local" name="available_from" value="{{ old('available_from', $assignment->available_from?->format('Y-m-d\TH:i')) }}"></div>
                <div class="col-md-6"><label class="form-label">Tenggat</label><input class="form-control" type="datetime-local" name="due_at" value="{{ old('due_at', $assignment->due_at?->format('Y-m-d\TH:i')) }}" required></div>
                <div class="col-md-3"><label class="form-label">Maks. file</label><input class="form-control" type="number" min="0" max="10" name="max_files" value="{{ old('max_files', $assignment->max_files ?? 3) }}"></div>
                <div class="col-md-3"><label class="form-label">Ukuran/file (KB)</label><input class="form-control" type="number" min="100" max="20480" name="max_file_size_kb" value="{{ old('max_file_size_kb', $assignment->max_file_size_kb ?? 5120) }}"></div>
                <div class="col-md-3"><label class="form-label">Maks. revisi</label><input class="form-control" type="number" min="0" max="5" name="max_revisions" value="{{ old('max_revisions', $assignment->max_revisions ?? 1) }}"></div>
                <div class="col-md-3 d-grid gap-2"><label class="form-check"><input class="form-check-input" type="checkbox" name="allow_late" value="1" @checked(old('allow_late', $assignment->allow_late))> Izinkan terlambat</label><label class="form-check"><input class="form-check-input" type="checkbox" name="is_published" value="1" @checked(old('is_published', $assignment->is_published))> Publikasikan</label></div>
                <div class="col-12"><label class="form-label">MIME file diizinkan</label><input class="form-control" name="allowed_mime_types_text" value="{{ old('allowed_mime_types_text', implode(', ', $assignment->allowed_mime_types ?? ['application/pdf', 'image/jpeg', 'image/png'])) }}"></div>
            </div>
            <div class="assignment-sticky"><button class="btn btn-primary">Simpan tugas</button></div>
        </form>
    </div>
@endsection
