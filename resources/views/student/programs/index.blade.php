@extends('layouts.dashboard')
@section('title', 'Program Saya')
@section('breadcrumb', 'Program Saya')
@section('content')
<x-ui.page-header
    eyebrow="Multi-program RKDD"
    title="Program Saya"
    description="Kelola program yang kamu ikuti. Masukkan kode pendaftaran jika ingin bergabung ke program atau pelatihan lain."
/>

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

<div class="row g-4 align-items-start">
    <div class="col-lg-7">
        <article class="skuad-card p-4 h-100">
            <div class="d-flex justify-content-between gap-3 align-items-start mb-3">
                <div>
                    <p class="skuad-eyebrow">Program aktif & riwayat</p>
                    <h2 class="h4 fw-bold mb-1">Program yang sudah diikuti</h2>
                    <p class="text-secondary mb-0">Hanya program yang sudah kamu ikuti yang dapat dipilih sebagai konteks aktif.</p>
                </div>
                <x-ui.badge variant="premium">{{ $memberships->count() }} program</x-ui.badge>
            </div>

            <div class="d-grid gap-3">
                @forelse($memberships as $membership)
                    @php($batch = $membership->programBatch)
                    <div class="p-3 rounded-4 border {{ $activeProgramBatch?->id === $batch?->id ? 'border-primary bg-primary-subtle' : 'bg-white' }}">
                        <div class="d-flex justify-content-between gap-3">
                            <div>
                                <strong class="d-block">{{ $batch?->program?->name ?? 'Program tidak tersedia' }}</strong>
                                <small class="text-secondary">{{ $batch?->institution?->name ?? '-' }} · {{ $batch?->period_label ?? $membership->academicYear?->name }} · {{ $membership->schoolClass?->name ?? '-' }}</small>
                            </div>
                            <x-ui.badge :variant="$membership->status === 'active' ? 'success' : 'warning'">{{ ucfirst($membership->status) }}</x-ui.badge>
                        </div>
                        <div class="d-flex flex-wrap gap-2 mt-3">
                            <span class="small text-secondary">Sebutan: {{ $batch?->participant_label ?? 'Peserta' }}</span>
                            @if($activeProgramBatch?->id === $batch?->id)
                                <span class="small fw-bold text-primary">Program aktif</span>
                            @else
                                <form method="POST" action="{{ route('program-context.update') }}">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="program_batch_id" value="{{ $batch?->id }}">
                                    <button class="btn btn-sm btn-outline-primary" type="submit">Jadikan aktif</button>
                                </form>
                            @endif
                        </div>
                    </div>
                @empty
                    <x-ui.empty-state icon="bi-diagram-3" title="Belum ada program aktif" description="Masukkan kode pendaftaran dari pembina/admin untuk mulai bergabung." />
                @endforelse
            </div>
        </article>
    </div>

    <div class="col-lg-5">
        <article class="skuad-card p-4">
            <p class="skuad-eyebrow">Gabung program</p>
            <h2 class="h4 fw-bold mb-2">Masukkan kode pendaftaran</h2>
            <p class="text-secondary">Kode mengarahkan kamu ke program, periode, dan kelompok/angkatan yang benar.</p>
            <form method="POST" action="{{ route('student.programs.join') }}" class="d-grid gap-3">
                @csrf
                <div>
                    <label class="form-label" for="code">Kode program</label>
                    <input class="form-control form-control-lg @error('code') is-invalid @enderror" id="code" name="code" value="{{ old('code') }}" placeholder="Contoh: KREATOR-2026" required autofocus>
                    @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <button class="btn btn-primary btn-lg" type="submit"><i class="bi bi-key me-1"></i> Gabung Program</button>
            </form>
        </article>
    </div>
</div>
@endsection
