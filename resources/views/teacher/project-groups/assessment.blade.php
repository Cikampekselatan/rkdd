@extends('layouts.dashboard')

@section('title', 'Nilai Proyek Kelompok - SKUAD Learning Hub')
@section('breadcrumb', 'Nilai Proyek Kelompok')

@section('content')
    <div class="grading-page">
        <x-ui.page-header eyebrow="Penilaian kelompok" :title="$project->title" :description="'Nilai ini akan masuk ke halaman nilai semua siswa aktif di '.$project->projectGroup->name.'.'" />

        <form class="card border-0 shadow-sm p-4" method="POST" action="{{ route('teacher.group-projects.assessment.update', $project) }}">
            @csrf
            @method('PUT')

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label" for="final_score">Nilai akhir kelompok</label>
                    <input class="form-control @error('final_score') is-invalid @enderror" id="final_score" name="final_score" type="number" min="0" max="100" step="0.01" value="{{ old('final_score', $assessment->final_score) }}" required>
                    @error('final_score')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-8">
                    <label class="form-label">Anggota yang menerima nilai</label>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach($project->projectGroup->members->where('is_active', true) as $member)
                            <span class="badge text-bg-light">{{ $member->student->name }}</span>
                        @endforeach
                    </div>
                </div>
                <div class="col-12">
                    <label class="form-label" for="feedback">Feedback untuk siswa</label>
                    <textarea class="form-control" id="feedback" name="feedback" rows="5">{{ old('feedback', $assessment->feedback) }}</textarea>
                </div>
                <div class="col-12">
                    <label class="form-label" for="private_note">Catatan privat guru/coach</label>
                    <textarea class="form-control" id="private_note" name="private_note" rows="4">{{ old('private_note', $assessment->private_note) }}</textarea>
                    <div class="form-text">Catatan ini tidak tampil di dashboard siswa.</div>
                </div>
                <div class="col-12">
                    <label class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_published" value="1" @checked(old('is_published', $assessment->is_published))>
                        <span class="form-check-label">Publikasikan nilai ke semua anggota kelompok</span>
                    </label>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-4">
                <a class="btn btn-outline-secondary" href="{{ route('teacher.project-groups.show', $project->projectGroup) }}">Batal</a>
                <button class="btn btn-primary">Simpan nilai</button>
            </div>
        </form>
    </div>
@endsection
