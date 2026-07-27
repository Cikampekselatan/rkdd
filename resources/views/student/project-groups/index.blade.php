@extends('layouts.dashboard')

@section('title', 'Kelompok Saya - SKUAD Learning Hub')
@section('breadcrumb', 'Kelompok Saya')

@section('content')
    <div class="assignment-page">
        <x-ui.page-header eyebrow="Kolaborasi" title="Kelompok saya" description="Ruang proyek kelompok yang kamu ikuti bersama teman SKUAD." />

        <div class="assignment-grid">
            @forelse($groups as $group)
                <a class="assignment-card" href="{{ route('student.project-groups.show', $group) }}">
                    <div class="assignment-card-top">
                        <span>{{ $group->academicYear->name }}</span>
                        <strong class="{{ $group->status === 'active' ? 'published' : 'draft' }}">{{ ucfirst($group->status) }}</strong>
                    </div>
                    <h2>{{ $group->name }}</h2>
                    <p>{{ $group->schoolClass->name }}</p>
                    <div>
                        <span><i class="bi bi-kanban"></i>{{ $group->projects_count }} proyek</span>
                    </div>
                </a>
            @empty
                <x-ui.empty-state title="Belum tergabung dalam kelompok proyek" description="Saat guru/coach menambahkan kamu ke kelompok, halaman kelompokmu akan muncul di sini." icon="bi-people" />
            @endforelse
        </div>

        {{ $groups->links() }}
    </div>
@endsection
