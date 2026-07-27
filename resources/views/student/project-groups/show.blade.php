@extends('layouts.dashboard')

@section('title', $group->name.' - SKUAD Learning Hub')
@section('breadcrumb', 'Kelompok Saya')

@section('content')
    <div class="assignment-page">
        <x-ui.page-header eyebrow="Kelompok saya" :title="$group->name" :description="$group->description ?: 'Ruang proyek kelompok program.'" />

        <div class="row g-4">
            <div class="col-lg-7">
                <section class="card border-0 shadow-sm p-4">
                    <h2 class="h5">Proyek kelompok</h2>
                    <div class="assignment-grid mt-3">
                        @forelse($group->projects as $project)
                            <article class="assignment-card">
                                <div class="assignment-card-top">
                                    <span>{{ ucfirst($project->status) }}</span>
                                    @if($project->assessment?->is_published)
                                        <strong class="published">Nilai terbit</strong>
                                    @endif
                                </div>
                                <h3 class="h5">{{ $project->title }}</h3>
                                <p>{{ \Illuminate\Support\Str::limit($project->description, 120) }}</p>
                                <div>
                                    <span><i class="bi bi-calendar-event"></i>{{ $project->due_at?->translatedFormat('d M Y') ?? 'Tanpa tenggat' }}</span>
                                    <span><i class="bi bi-award"></i>{{ $project->assessment?->is_published ? number_format((float) $project->assessment->final_score, 0) : 'Belum dinilai' }}</span>
                                </div>
                                @if($project->evidence_url)
                                    <a class="btn btn-sm btn-outline-primary mt-3" href="{{ $project->evidence_url }}" target="_blank" rel="noopener">Buka bukti karya</a>
                                @endif
                            </article>
                        @empty
                            <x-ui.empty-state title="Belum ada proyek tampil" description="Proyek akan muncul setelah guru/coach mempublikasikannya." icon="bi-kanban" />
                        @endforelse
                    </div>
                </section>
            </div>

            <div class="col-lg-5">
                <section class="card border-0 shadow-sm p-4">
                    <h2 class="h5">Anggota kelompok</h2>
                    <div class="list-group list-group-flush">
                        @foreach($group->members as $member)
                            <div class="list-group-item px-0">
                                <strong>{{ $member->student->name }}</strong>
                                @if($member->role)
                                    <br><small>{{ $member->role }}</small>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </section>
            </div>
        </div>
    </div>
@endsection
