@extends('layouts.dashboard')
@section('title', $student->name.' - Detail Peserta')
@section('breadcrumb', 'Detail Peserta')
@section('content')
@php($latestMembership = $student->classMemberships->sortByDesc('academic_year_id')->first())
<div data-student-skeleton class="d-grid gap-3"><x-ui.skeleton :lines="3" avatar /><x-ui.skeleton :lines="4" /><x-ui.skeleton :lines="3" /></div>
<div data-student-detail hidden>
    <x-ui.page-header eyebrow="Profil peserta 360°" :title="$student->name" :description="$student->email">
        <x-slot:actions>
            @can('changeStudentStatus', \App\Models\User::class)
                @if($student->status === \App\Enums\UserStatus::Archived || $student->trashed())
                    <form method="POST" action="{{ route('admin.students.reset-onboarding', $student) }}" data-confirm="Reset peserta ini ke onboarding agar bisa memasukkan kode pendaftaran lagi?">@csrf @method('PATCH')<x-ui.button type="submit" icon="bi-arrow-counterclockwise">Reset ke kode pendaftaran</x-ui.button></form>
                    <form method="POST" action="{{ route('admin.students.purge-test', $student) }}" data-confirm="Hapus permanen data peserta test ini? Tindakan ini tidak bisa dibatalkan dan hanya berhasil jika belum ada riwayat belajar.">@csrf @method('DELETE')<x-ui.button type="submit" variant="danger" icon="bi-trash">Hapus permanen data test</x-ui.button></form>
                @elseif($student->status === \App\Enums\UserStatus::Active)
                    <button class="btn btn-outline-danger" type="button" data-bs-toggle="modal" data-bs-target="#deactivateStudent"><i class="bi bi-person-dash" aria-hidden="true"></i> Nonaktifkan keanggotaan</button>
                @elseif(in_array($student->status, [\App\Enums\UserStatus::Inactive, \App\Enums\UserStatus::Suspended], true))
                    <form method="POST" action="{{ route('admin.students.activate', $student) }}" data-confirm="Aktifkan kembali keanggotaan peserta ini?">@csrf @method('PATCH')<x-ui.button type="submit" icon="bi-play-circle">Aktifkan kembali</x-ui.button></form>
                @endif
            @endcan
        </x-slot:actions>
    </x-ui.page-header>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    <div class="row g-3">
        <div class="col-lg-4"><article class="skuad-card p-4 text-center h-100">
            <x-ui.avatar :name="$student->name" size="xl" :status="$student->status === \App\Enums\UserStatus::Active ? 'online' : null" />
            <h2 class="h4 fw-bold mt-3">{{ $student->studentProfile?->nickname ?? $student->name }}</h2>
            <p class="text-secondary">{{ $student->studentProfile?->grade_level ? 'Kelas '.$student->studentProfile->grade_level.' - '.$student->studentProfile->school_class_name : 'Kelas sekolah belum dilengkapi' }}</p>
            <x-ui.badge :variant="$student->status === \App\Enums\UserStatus::Active ? 'success' : ($student->status === \App\Enums\UserStatus::Suspended ? 'danger' : 'warning')">{{ ucfirst($student->status->value) }}</x-ui.badge>
            <hr>
            <dl class="student-detail-list"><div><dt>Nomor siswa</dt><dd>{{ $student->studentProfile?->student_number ?? '-' }}</dd></div><div><dt>NISN</dt><dd>{{ $student->studentProfile?->nisn ?? '-' }}</dd></div><div><dt>Bergabung</dt><dd>{{ $student->studentProfile?->joined_at?->format('d M Y') ?? '-' }}</dd></div></dl>
        </article></div>
        <div class="col-lg-8"><div class="d-grid gap-3">
            <article class="skuad-card p-4"><p class="skuad-eyebrow">Keanggotaan program</p><div class="row g-3"><div class="col-md-4"><small class="text-secondary">Kelompok/angkatan</small><strong class="d-block">{{ $student->studentProfile?->schoolClass?->name ?? '-' }}</strong></div><div class="col-md-4"><small class="text-secondary">Tingkat sekolah</small><strong class="d-block">{{ $student->studentProfile?->grade_level ? 'Kelas '.$student->studentProfile->grade_level : '-' }}</strong></div><div class="col-md-4"><small class="text-secondary">Kelas asal</small><strong class="d-block">{{ $student->studentProfile?->school_class_name ?? '-' }}</strong></div></div></article>
            <article class="skuad-card p-4"><p class="skuad-eyebrow">Orang tua/wali</p><div class="row g-3"><div class="col-md-4"><small class="text-secondary">Nama</small><strong class="d-block">{{ $student->studentProfile?->parent_name ?? '-' }}</strong></div><div class="col-md-4"><small class="text-secondary">Telepon</small><strong class="d-block">{{ $student->studentProfile?->parent_phone ?? '-' }}</strong></div><div class="col-md-4"><small class="text-secondary">Hubungan</small><strong class="d-block">{{ $student->studentProfile?->guardian_relationship ?? '-' }}</strong></div></div></article>
            <article class="skuad-card p-4"><p class="skuad-eyebrow">Akses & minat</p><div class="mb-3"><small class="text-secondary d-block mb-2">Perangkat</small>@foreach($student->onboardingResponse?->device_access ?? [] as $item)<x-ui.badge class="me-1">{{ ucfirst($item) }}</x-ui.badge>@endforeach</div><div><small class="text-secondary d-block mb-2">Minat</small>@foreach($student->onboardingResponse?->interests ?? [] as $item)<x-ui.badge variant="premium" class="me-1">{{ ucfirst($item) }}</x-ui.badge>@endforeach</div></article>
        </div></div>
    </div>

    <div class="row g-3 mt-0">
        <div class="col-lg-6"><article class="skuad-card p-4 h-100"><p class="skuad-eyebrow">Riwayat keanggotaan</p>@forelse($student->classMemberships->sortByDesc('academic_year_id') as $membership)<div class="py-2 border-bottom"><div class="d-flex justify-content-between gap-2"><strong>{{ $membership->schoolClass->name }}</strong><x-ui.badge :variant="$membership->status === 'active' ? 'success' : 'warning'">{{ ucfirst($membership->status) }}</x-ui.badge></div><small class="text-secondary">Masuk {{ $membership->joined_at?->format('d M Y') }}@if($membership->left_at) - Keluar {{ $membership->left_at->format('d M Y') }}@endif</small>@if($membership->exit_reason)<p class="small mb-0 mt-1">{{ \App\Enums\StudentExitReason::from($membership->exit_reason)->label() }}{{ $membership->exit_notes ? ': '.$membership->exit_notes : '' }}</p>@endif</div>@empty<p class="text-secondary mb-0">Belum ada riwayat keanggotaan.</p>@endforelse</article></div>
        <div class="col-lg-6"><article class="skuad-card p-4 h-100"><p class="skuad-eyebrow">Login terbaru</p>@forelse($student->authenticationLogs as $log)<div class="d-flex justify-content-between gap-3 py-2 border-bottom"><span>{{ str($log->event)->replace('_', ' ')->title() }}</span><small class="text-secondary">{{ $log->created_at?->diffForHumans() }}</small></div>@empty<p class="text-secondary mb-0">Belum ada audit login.</p>@endforelse</article></div>
    </div>

    @can('changeStudentStatus', \App\Models\User::class)
        @unless($student->status === \App\Enums\UserStatus::Archived || $student->trashed())
            <form class="mt-4" method="POST" action="{{ route('admin.students.destroy', $student) }}" data-confirm="Arsipkan data peserta ini?">@csrf @method('DELETE')<x-ui.button type="submit" variant="ghost" icon="bi-archive">Arsipkan peserta</x-ui.button></form>
        @endunless
    @endcan
</div>
@endsection

@push('overlays')
@can('changeStudentStatus', \App\Models\User::class)
<div class="modal fade" id="deactivateStudent" tabindex="-1" aria-labelledby="deactivateStudentLabel" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
    <form method="POST" action="{{ route('admin.students.deactivate', $student) }}">@csrf @method('PATCH')
        <div class="modal-header"><div><p class="skuad-eyebrow mb-1">Riwayat keanggotaan</p><h2 class="modal-title h5" id="deactivateStudentLabel">Nonaktifkan {{ $student->name }}</h2></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
        <div class="modal-body"><p class="text-secondary">Akun tidak dihapus. Data tugas, nilai, kehadiran, dan portofolio tetap tersimpan.</p><label class="form-label" for="exit_reason">Alasan keluar</label><select class="form-select" id="exit_reason" name="exit_reason" required><option value="">Pilih alasan</option>@foreach(\App\Enums\StudentExitReason::cases() as $reason)<option value="{{ $reason->value }}">{{ $reason->label() }}</option>@endforeach</select><label class="form-label mt-3" for="exit_notes">Catatan tambahan</label><textarea class="form-control" id="exit_notes" name="exit_notes" rows="3" maxlength="1000" placeholder="Opsional"></textarea></div>
        <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-danger">Nonaktifkan keanggotaan</button></div>
    </form>
</div></div></div>
@endcan
@endpush
