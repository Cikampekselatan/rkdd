@extends('layouts.dashboard')

@section('title', $group->name.' - RKDD')
@section('breadcrumb', 'Kelompok Proyek')

@section('content')
    <div class="assignment-page">
        <x-ui.page-header eyebrow="Kelompok proyek" :title="$group->name" :description="$group->description ?: 'Kelompok proyek kolaboratif peserta program.'">
            <x-slot:actions>
                <x-ui.button :href="route('teacher.project-groups.edit', $group)" variant="outline" icon="bi-pencil">Edit anggota</x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="row g-4">
            <div class="col-lg-7">
                <section class="card border-0 shadow-sm p-4 mb-4">
                    <h2 class="h5">Proyek kelompok</h2>
                    <div class="assignment-grid mt-3">
                        @forelse($group->projects as $item)
                            <article class="assignment-card">
                                <div class="assignment-card-top">
                                    <span>{{ ucfirst($item->status) }}</span>
                                    <strong class="{{ $item->is_published ? 'published' : 'draft' }}">{{ $item->is_published ? 'Terbit' : 'Draf' }}</strong>
                                </div>
                                <h3 class="h5">{{ $item->title }}</h3>
                                <p>{{ \Illuminate\Support\Str::limit($item->description, 100) }}</p>
                                <div>
                                    <span><i class="bi bi-calendar-event"></i>{{ $item->due_at?->translatedFormat('d M Y') ?? 'Tanpa tenggat' }}</span>
                                    <span><i class="bi bi-award"></i>{{ $item->assessment ? number_format((float) $item->assessment->final_score, 0) : 'Belum dinilai' }}</span>
                                </div>
                                <a class="btn btn-sm btn-primary mt-3" href="{{ route('teacher.group-projects.assessment.edit', $item) }}">Nilai proyek</a>
                            </article>
                        @empty
                            <x-ui.empty-state title="Belum ada proyek" description="Tambahkan proyek pertama untuk kelompok ini." icon="bi-kanban" />
                        @endforelse
                    </div>
                </section>
            </div>

            <div class="col-lg-5">
                <section class="card border-0 shadow-sm p-4 mb-4">
                    <h2 class="h5">Anggota</h2>
                    <div class="list-group list-group-flush">
                        @foreach($group->members as $member)
                            <div class="list-group-item px-0 d-flex justify-content-between gap-3">
                                <span>
                                    <strong>{{ $member->student->name }}</strong><br>
                                    <small>{{ $member->student->email }}</small>
                                </span>
                                <span class="badge text-bg-{{ $member->is_active ? 'success' : 'secondary' }}">{{ $member->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                            </div>
                        @endforeach
                    </div>
                </section>

                <section class="card border-0 shadow-sm p-4">
                    <h2 class="h5">Tambah proyek</h2>
                    <form method="POST" action="{{ route('teacher.project-groups.projects.store', $group) }}" class="d-grid gap-3">
                        @csrf
                        <input class="form-control @error('title') is-invalid @enderror" name="title" value="{{ old('title') }}" required placeholder="Judul proyek kelompok">
                        @error('title')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        <textarea class="form-control" name="description" rows="4" placeholder="Instruksi atau target proyek">{{ old('description') }}</textarea>
                        <input class="form-control" name="evidence_url" value="{{ old('evidence_url') }}" placeholder="URL bukti karya opsional">
                        <input class="form-control" type="datetime-local" name="due_at" value="{{ old('due_at') }}">
                        <select class="form-select" name="status">
                            <option value="active">Aktif</option>
                            <option value="completed">Selesai</option>
                            <option value="archived">Arsip</option>
                        </select>
                        <label class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_published" value="1" @checked(old('is_published'))>
                            <span class="form-check-label">Tampilkan ke siswa</span>
                        </label>
                        <button class="btn btn-primary">Tambah proyek</button>
                    </form>
                </section>
            </div>
        </div>
    </div>
@endsection
