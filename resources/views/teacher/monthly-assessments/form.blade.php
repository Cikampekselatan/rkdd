@extends('layouts.dashboard')

@section('title', ($assessment->exists ? 'Edit' : 'Input').' Asesmen Bulanan')
@section('breadcrumb', 'Form asesmen bulanan')

@section('content')
    <div class="grading-page monthly-assessment-page">
        <x-ui.page-header
            eyebrow="Asesmen perkembangan"
            :title="$assessment->exists ? 'Perbarui asesmen bulanan' : 'Input asesmen bulanan'"
            description="Nilai akhir dihitung otomatis dari bobot asesmen SKUAD: produk 35%, proses 25%, kolaborasi 15%, presentasi 15%, etika 10%."
        />

        @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

        <form class="rubric-form monthly-assessment-form" method="POST" action="{{ $assessment->exists ? route('teacher.monthly-assessments.update', $assessment) : route('teacher.monthly-assessments.store') }}">
            @csrf
            @if($assessment->exists)@method('PUT')@endif

            <section class="monthly-form-section">
                <h2>Periode dan siswa</h2>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label" for="academic_year_id">Tahun ajaran</label>
                        <select class="form-select" id="academic_year_id" name="academic_year_id" required>
                            @foreach($academicYears as $year)
                                <option value="{{ $year->id }}" @selected(old('academic_year_id', $assessment->academic_year_id) == $year->id)>{{ $year->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="class_id">Kelompok</label>
                        <select class="form-select" id="class_id" name="class_id" required>
                            @foreach($classes as $class)
                                <option value="{{ $class->id }}" @selected(old('class_id', $assessment->class_id) == $class->id)>{{ $class->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="user_id">Siswa</label>
                        <select class="form-select" id="user_id" name="user_id" required>
                            @foreach($students as $student)
                                <option value="{{ $student->id }}" @selected(old('user_id', $assessment->user_id) == $student->id)>{{ $student->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="semester">Semester</label>
                        <select class="form-select" id="semester" name="semester" required>
                            <option value="1" @selected((int) old('semester', $assessment->semester ?: request('semester', 1)) === 1)>Semester 1</option>
                            <option value="2" @selected((int) old('semester', $assessment->semester ?: request('semester', 1)) === 2)>Semester 2</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="assessment_month">Bulan semester</label>
                        <select class="form-select" id="assessment_month" name="assessment_month" required>
                            @for($month = 1; $month <= 6; $month++)
                                <option value="{{ $month }}" @selected((int) old('assessment_month', $assessment->assessment_month ?: 1) === $month)>Bulan {{ $month }}</option>
                            @endfor
                        </select>
                    </div>
                </div>
                <small class="text-secondary">Jika perlu mengganti tahun/kelas, sebaiknya mulai dari filter halaman daftar agar pilihan siswa ikut sesuai.</small>
            </section>

            <section class="monthly-form-section">
                <h2>Produk dan bukti</h2>
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label" for="product_summary">Ringkasan produk/bukti bulan ini</label>
                        <textarea class="form-control" id="product_summary" name="product_summary" rows="3" maxlength="3000" placeholder="Contoh: poster digital, foto cerita, kampanye, video, prompt, prototipe, katalog, atau proyek akhir.">{{ old('product_summary', $assessment->product_summary) }}</textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="evidence_url">URL bukti/portofolio <span class="text-secondary">(opsional)</span></label>
                        <input class="form-control" id="evidence_url" name="evidence_url" type="url" value="{{ old('evidence_url', $assessment->evidence_url) }}" placeholder="https://drive.google.com/...">
                    </div>
                </div>
            </section>

            <section class="monthly-form-section">
                <h2>Skor komponen</h2>
                <div class="monthly-score-grid">
                    @foreach($components as $field => $component)
                        <label>
                            <span>{{ $component['label'] }}</span>
                            <small>Bobot {{ $component['weight'] }}%</small>
                            <input class="form-control" name="{{ $field }}" type="number" min="0" max="100" step="0.01" value="{{ old($field, $assessment->{$field} ?? 0) }}" required>
                        </label>
                    @endforeach
                </div>
            </section>

            <section class="monthly-form-section">
                <h2>Deskripsi hasil, remedial, dan pengayaan</h2>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="strengths">Kekuatan siswa</label>
                        <textarea class="form-control" id="strengths" name="strengths" rows="3">{{ old('strengths', $assessment->strengths) }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="improvement_targets">Target perbaikan</label>
                        <textarea class="form-control" id="improvement_targets" name="improvement_targets" rows="3">{{ old('improvement_targets', $assessment->improvement_targets) }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="remedial_plan">Remedial bila diperlukan</label>
                        <textarea class="form-control" id="remedial_plan" name="remedial_plan" rows="3" placeholder="Contoh: revisi bagian yang belum memenuhi kriteria dan unggah bukti terbaru.">{{ old('remedial_plan', $assessment->remedial_plan) }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="enrichment_plan">Pengayaan</label>
                        <textarea class="form-control" id="enrichment_plan" name="enrichment_plan" rows="3" placeholder="Contoh: fitur tambahan, tutorial, audiens berbeda, atau peran mentor.">{{ old('enrichment_plan', $assessment->enrichment_plan) }}</textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="teacher_note">Catatan privat guru</label>
                        <textarea class="form-control" id="teacher_note" name="teacher_note" rows="3">{{ old('teacher_note', $assessment->teacher_note) }}</textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_published" value="1" @checked(old('is_published', $assessment->is_published))>
                            <span class="form-check-label">Tandai siap dipublikasikan ke laporan perkembangan</span>
                        </label>
                    </div>
                </div>
            </section>

            <div class="grading-sticky">
                <a class="btn btn-outline-secondary" href="{{ route('teacher.monthly-assessments.index') }}">Batal</a>
                <button class="btn btn-primary"><i class="bi bi-cloud-check"></i> Simpan asesmen</button>
            </div>
        </form>
    </div>
@endsection
