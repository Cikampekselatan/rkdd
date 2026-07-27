@extends('layouts.dashboard')
@section('title', 'Absen Pengajar - SKUAD Learning Hub')
@section('breadcrumb', 'Absen pengajar')
@section('content')
<div class="phase12-page">
    <x-ui.page-header eyebrow="Administrasi kegiatan" title="Absen pengajar" description="Rekap kegiatan pembina, penugasan, tanda tangan, dan verifikasi bulanan.">
        <x-slot:actions>
            <x-ui.button :href="route('activity-logs.print-index', request()->query())" variant="outline" icon="bi-printer" target="_blank">Cetak laporan</x-ui.button>
            @can('create', \App\Models\TeacherActivityLog::class)
                <x-ui.button :href="route('activity-logs.create')" icon="bi-plus-lg">Buat absen</x-ui.button>
            @endcan
        </x-slot:actions>
    </x-ui.page-header>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    <form class="phase12-filter" method="GET"><select class="form-select" name="academic_year_id"><option value="">Semua tahun</option>@foreach($academicYears as $year)<option value="{{ $year->id }}" @selected(($filters['academic_year_id'] ?? '') == $year->id)>{{ $year->name }}</option>@endforeach</select><input class="form-control" type="month" name="month" value="{{ $filters['month'] ?? '' }}">@unless(auth()->user()->hasRole(\App\Enums\RoleSlug::Teacher))<select class="form-select" name="teacher_id"><option value="">Semua pembina</option>@foreach($teachers as $teacher)<option value="{{ $teacher->id }}" @selected(($filters['teacher_id'] ?? '') == $teacher->id)>{{ $teacher->name }}</option>@endforeach</select>@endunless<select class="form-select" name="status"><option value="">Semua status</option>@foreach(\App\Enums\TeacherActivityStatus::cases() as $status)<option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>{{ $status->label() }}</option>@endforeach</select><button class="btn btn-outline-primary" type="submit">Filter</button></form>
    <div class="phase12-list">
        @forelse($logs as $log)<a class="phase12-row" href="{{ route('activity-logs.show', $log) }}"><span class="phase12-number">{{ str_pad($log->log_number, 3, '0', STR_PAD_LEFT) }}</span><div><small>{{ $log->activity_date->translatedFormat('d F Y') }} · {{ $log->teacher->name }}</small><h2>{{ $log->material }}</h2><p>{{ \Illuminate\Support\Str::limit($log->activities, 120) }}</p></div><span class="phase12-status status-{{ $log->status->value }}">{{ $log->status->label() }}</span><i class="bi bi-chevron-right"></i></a>@empty<x-ui.empty-state title="Belum ada absen pengajar" description="Buat catatan kegiatan pertama untuk memulai rekap bulanan." icon="bi-journal-check" />@endforelse
    </div>{{ $logs->links() }}
</div>
@endsection
