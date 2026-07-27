@extends('layouts.dashboard')

@php
    $codeProgramContext = app(\App\Services\ProgramContextService::class);
    $participantLabel = $codeProgramContext->participantLabel(auth()->user());
    $groupLabel = $codeProgramContext->groupLabel(auth()->user());
    $periodLabel = $codeProgramContext->periodLabel(auth()->user());
@endphp

@section('title', 'Kode Pendaftaran - RKDD')
@section('breadcrumb', 'Kode Pendaftaran')

@section('content')
    <x-ui.page-header eyebrow="Pendaftaran program" title="Kode Pendaftaran" :description="'Buat dan kelola kode onboarding '.strtolower($participantLabel).'. Kode baru dapat dilihat lengkap dan disalin oleh admin/super admin.'">
        <x-slot:actions>
            <x-ui.button :href="route('admin.registration-codes.create')" icon="bi-plus-lg">Buat kode</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    @if (session('generated_code'))
        <div class="registration-code-reveal mb-4" role="status">
            <div>
                <p class="skuad-eyebrow mb-1">Kode baru</p>
                <h2>Kode berhasil dibuat</h2>
                <p>Salin dan bagikan melalui kanal sekolah yang aman. Kode baru juga tetap tersedia di tabel selama belum dihapus.</p>
            </div>
            <div class="registration-code-value">
                <code data-generated-code>{{ session('generated_code') }}</code>
                <button class="skuad-icon-button" type="button" data-copy-code aria-label="Salin kode"><i class="bi bi-copy"></i></button>
            </div>
        </div>
    @endif

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @error('registration_code')
        <div class="alert alert-danger">{{ $message }}</div>
    @enderror

    <div class="skuad-table-wrap">
        <div class="table-responsive">
            <table class="table skuad-table">
                <thead>
                    <tr><th>Nama</th><th>Kode</th><th>Program</th><th>{{ $periodLabel }}/{{ $groupLabel }}</th><th>Penggunaan</th><th>Masa aktif</th><th>Status</th><th class="text-end">Aksi</th></tr>
                </thead>
                <tbody>
                    @forelse ($registrationCodes as $registrationCode)
                        <tr>
                            <td><strong class="d-block">{{ $registrationCode->name }}</strong><small class="text-secondary">oleh {{ $registrationCode->creator->name }}</small></td>
                            <td>
                                @if ($registrationCode->plain_code_encrypted)
                                    <div class="registration-code-value compact">
                                        <code data-generated-code>{{ $registrationCode->plain_code_encrypted }}</code>
                                        <button class="skuad-icon-button" type="button" data-copy-code aria-label="Salin kode {{ $registrationCode->name }}"><i class="bi bi-copy"></i></button>
                                    </div>
                                @else
                                    <code>••••-{{ $registrationCode->code_hint }}</code>
                                    <small class="d-block text-secondary">Kode lama tidak bisa dipulihkan. Buat kode baru untuk dibagikan.</small>
                                @endif
                            </td>
                            <td><span class="d-block">{{ $registrationCode->programBatch?->program?->name ?? 'SKUAD' }}</span><small class="text-secondary">{{ $registrationCode->programBatch?->institution?->name ?? 'Batch default' }}</small></td>
                            <td><span class="d-block">{{ $registrationCode->academicYear->name }}</span><small class="text-secondary">{{ $registrationCode->schoolClass?->name ?? 'Semua kelompok' }}</small></td>
                            <td><strong>{{ $registrationCode->used_count }}</strong> / {{ $registrationCode->max_uses ?? 'Tanpa batas' }}</td>
                            <td><small class="d-block">{{ $registrationCode->starts_at?->format('d M Y H:i') ?? 'Langsung aktif' }}</small><small class="text-secondary">s.d. {{ $registrationCode->expires_at?->format('d M Y H:i') ?? 'Tanpa batas' }}</small></td>
                            <td>
                                @if (! $registrationCode->is_active)
                                    <x-ui.badge variant="neutral">Nonaktif</x-ui.badge>
                                @elseif ($registrationCode->hasReachedUsageLimit())
                                    <x-ui.badge variant="danger">Limit tercapai</x-ui.badge>
                                @elseif ($registrationCode->expires_at?->isPast())
                                    <x-ui.badge variant="warning">Kedaluwarsa</x-ui.badge>
                                @else
                                    <x-ui.badge variant="success">Aktif</x-ui.badge>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-1">
                                    <a class="skuad-icon-button" href="{{ route('admin.registration-codes.edit', $registrationCode) }}" aria-label="Edit {{ $registrationCode->name }}"><i class="bi bi-pencil"></i></a>
                                    <button class="skuad-icon-button text-danger" type="button" data-bs-toggle="modal" data-bs-target="#deleteCodeModal" data-delete-registration-code data-delete-url="{{ route('admin.registration-codes.destroy', $registrationCode) }}" data-code-name="{{ $registrationCode->name }}" aria-label="Hapus {{ $registrationCode->name }}"><i class="bi bi-trash"></i></button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8"><x-ui.empty-state title="Belum ada kode pendaftaran" :description="'Buat kode pertama untuk memulai onboarding '.strtolower($participantLabel).'.'" icon="bi-key"><x-slot:action><x-ui.button :href="route('admin.registration-codes.create')" icon="bi-plus-lg">Buat kode</x-ui.button></x-slot:action></x-ui.empty-state></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $registrationCodes->links() }}</div>
@endsection

@push('overlays')
    <div class="modal fade skuad-modal" id="deleteCodeModal" tabindex="-1" aria-labelledby="deleteCodeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body p-4 p-md-5 text-center">
                    <span class="skuad-modal-icon mb-3"><i class="bi bi-trash3"></i></span>
                    <h2 class="h4 fw-bold" id="deleteCodeModalLabel">Hapus kode pendaftaran?</h2>
                    <p class="text-secondary">Kode <strong data-delete-code-name></strong> akan dihapus permanen jika belum pernah digunakan.</p>
                    <form method="POST" data-delete-code-form>
                        @csrf
                        @method('DELETE')
                        <div class="d-grid d-sm-flex justify-content-sm-center gap-2 mt-4">
                            <x-ui.button variant="outline" data-bs-dismiss="modal">Batal</x-ui.button>
                            <x-ui.button type="submit" variant="danger">Ya, hapus</x-ui.button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endpush
