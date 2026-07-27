@extends('layouts.dashboard')

@section('title', 'Pembelajaran - SKUAD Learning Hub')
@section('breadcrumb', 'Pembelajaran')

@section('content')
    <div class="learning-page">
        <x-ui.page-header eyebrow="Pembelajaran program" title="Ruang Belajar Program" :description="'Kelola modul dan pertemuan '.($activeBatch?->program?->name ?? 'program aktif').'. Jumlah pertemuan mengikuti kebutuhan masing-masing program, bisa ditambah atau dikurangi manual.'">
            <x-slot:actions>
                <x-ui.button :href="route('teacher.learning.modules.create')" variant="outline" icon="bi-folder-plus">Tambah modul</x-ui.button>
                <x-ui.button :href="route('teacher.learning.sessions.create')" icon="bi-plus-lg">Tambah pertemuan</x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>

        @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        @error('learning_module')<div class="alert alert-danger">{{ $message }}</div>@enderror
        @error('learning_session')<div class="alert alert-danger">{{ $message }}</div>@enderror

        <form class="learning-filter" method="GET">
            <label class="form-label mb-0" for="academic_year_id">Tahun ajaran</label>
            <select class="form-select" id="academic_year_id" name="academic_year_id" onchange="this.form.submit()">
                <option value="">Semua tahun</option>
                @foreach ($academicYears as $year)
                    <option value="{{ $year->id }}" @selected($selectedAcademicYear === $year->id)>{{ $year->name }}</option>
                @endforeach
            </select>
        </form>

        <div class="learning-module-stack">
            @forelse ($modules as $module)
                <section class="learning-module-card">
                    <div class="learning-module-header">
                        <div class="learning-module-number">{{ str_pad($module->module_number, 2, '0', STR_PAD_LEFT) }}</div>
                        <div class="flex-grow-1">
                            <div class="d-flex flex-wrap gap-2 mb-2">
                                <x-ui.badge :variant="$module->is_active ? 'success' : 'neutral'">{{ $module->is_active ? 'Aktif' : 'Nonaktif' }}</x-ui.badge>
                                <x-ui.badge>{{ $module->academicYear->name }}</x-ui.badge>
                            </div>
                            <h2>{{ $module->title }}</h2>
                            <p>{{ $module->description ?: 'Belum ada deskripsi modul.' }}</p>
                        </div>
                        <div class="learning-module-actions">
                            <a class="skuad-icon-button" href="{{ route('teacher.learning.sessions.create', ['module' => $module->id, 'academic_year_id' => $module->academic_year_id]) }}" aria-label="Tambah pertemuan ke modul"><i class="bi bi-plus-lg"></i></a>
                            <a class="skuad-icon-button" href="{{ route('teacher.learning.modules.edit', $module) }}" aria-label="Edit modul"><i class="bi bi-pencil"></i></a>
                            <form method="POST" action="{{ route('teacher.learning.modules.destroy', $module) }}" data-confirm="Arsipkan modul ini?">@csrf @method('DELETE')<button class="skuad-icon-button text-danger" aria-label="Arsipkan modul"><i class="bi bi-archive"></i></button></form>
                        </div>
                    </div>

                    <div class="learning-session-grid">
                        @forelse ($module->sessions as $session)
                            <article class="learning-session-card">
                                <div class="d-flex justify-content-between gap-3">
                                    <span class="learning-session-number">Pertemuan {{ $session->session_number }}</span>
                                    <x-ui.badge :variant="$session->status->badgeVariant()">{{ $session->status->label() }}</x-ui.badge>
                                </div>
                                <h3>{{ $session->title }}</h3>
                                <div class="learning-session-meta"><span><i class="bi bi-clock"></i>{{ $session->duration_minutes }} menit</span><span><i class="bi bi-layers"></i>{{ $session->materials->count() }} materi</span><span>Semester {{ $session->semester }}</span></div>
                                <div class="learning-session-actions">
                                    <x-ui.button :href="route('teacher.learning.sessions.preview', $session)" variant="ghost" icon="bi-eye">Preview</x-ui.button>
                                    <x-ui.button :href="route('teacher.learning.sessions.edit', $session)" variant="outline" icon="bi-pencil">Kelola</x-ui.button>
                                    @unless ($session->status->isVisibleToStudents())
                                        <form method="POST" action="{{ route('teacher.learning.sessions.publish', $session) }}" data-confirm="Publikasikan pertemuan ini kepada siswa?">@csrf @method('PATCH')<x-ui.button type="submit" icon="bi-send">Publish</x-ui.button></form>
                                    @endunless
                                </div>
                            </article>
                        @empty
                            <div class="learning-module-empty">Belum ada pertemuan pada modul ini.</div>
                        @endforelse
                    </div>
                </section>
            @empty
                <div class="skuad-card"><x-ui.empty-state title="Belum ada modul" description="Tambahkan modul pembelajaran pertama untuk program aktif." icon="bi-journal-richtext" /></div>
            @endforelse
        </div>

        <div>{{ $modules->links() }}</div>
    </div>
@endsection
