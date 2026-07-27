@extends('layouts.dashboard')

@section('title', 'Kelompok Proyek - RKDD')
@section('breadcrumb', 'Kelompok Proyek')

@section('content')
    <div class="assignment-page">
        <x-ui.page-header eyebrow="Kolaborasi" title="Kelompok proyek program" description="Kelola tim proyek lintas angkatan/kelompok dan publikasikan nilai kelompok ke semua anggota.">
            <x-slot:actions>
                <x-ui.button :href="route('teacher.project-groups.create')" icon="bi-plus-lg">Buat kelompok</x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="assignment-grid">
            @forelse($groups as $group)
                <a class="assignment-card" href="{{ route('teacher.project-groups.show', $group) }}">
                    <div class="assignment-card-top">
                        <span>{{ $group->academicYear->name }}</span>
                        <strong class="{{ $group->status === 'active' ? 'published' : 'draft' }}">{{ ucfirst($group->status) }}</strong>
                    </div>
                    <h2>{{ $group->name }}</h2>
                    <p>{{ $group->schoolClass->name }}</p>
                    <div>
                        <span><i class="bi bi-people"></i>{{ $group->active_members_count }} anggota</span>
                        <span><i class="bi bi-kanban"></i>{{ $group->projects_count }} proyek</span>
                    </div>
                </a>
            @empty
                <x-ui.empty-state title="Belum ada kelompok proyek" description="Buat kelompok pertama, pilih anggota, lalu tambahkan proyek kelompok." icon="bi-people" />
            @endforelse
        </div>

        {{ $groups->links() }}
    </div>
@endsection
