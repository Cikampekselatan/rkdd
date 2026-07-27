@extends('layouts.dashboard')
@section('title', 'Staff - SKUAD Learning Hub')
@section('breadcrumb', 'Staff')
@section('content')
<x-ui.page-header eyebrow="Master pengguna" title="Staff" description="Akun staff dibuat oleh administrator dan menggunakan login lokal.">
    <x-slot:actions><x-ui.button :href="route('admin.staff.create')" icon="bi-person-plus">Tambah staff</x-ui.button></x-slot:actions>
</x-ui.page-header>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
<div class="skuad-table-wrap"><div class="table-responsive"><table class="table skuad-table">
    <thead><tr><th>Staff</th><th>Role</th><th>Penempatan program</th><th>Nomor pegawai</th><th>Spesialisasi</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
    <tbody>
        @forelse($staffMembers as $staff)
            <tr>
                <td><div class="d-flex align-items-center gap-2"><x-ui.avatar :name="$staff->name" size="sm" /><div><strong class="d-block">{{ $staff->name }}</strong><small class="text-secondary">{{ $staff->email }}</small></div></div></td>
                <td>{{ $staff->roles->first()?->name }}</td>
                <td>
                    @forelse($staff->assignedProgramBatches as $batch)
                        <span class="badge text-bg-light border d-inline-flex mb-1">{{ $batch->program?->name }} · {{ $batch->institution?->name }}</span>
                    @empty
                        <span class="text-secondary small">Belum ditempatkan</span>
                    @endforelse
                </td>
                <td>{{ $staff->teacherProfile?->employee_number ?? '-' }}</td>
                <td>{{ $staff->teacherProfile?->specialization ?? '-' }}</td>
                <td><x-ui.badge :variant="$staff->status === \App\Enums\UserStatus::Active ? 'success' : 'danger'">{{ $staff->status->value }}</x-ui.badge></td>
                <td class="text-end"><div class="d-inline-flex">
                    <a class="skuad-icon-button" href="{{ route('admin.staff.edit', $staff) }}" aria-label="Edit staff {{ $staff->name }}"><i class="bi bi-pencil" aria-hidden="true"></i></a>
                    <form method="POST" action="{{ route('admin.staff.destroy', $staff) }}" data-confirm="Arsipkan akun staff ini?">@csrf @method('DELETE')<button class="skuad-icon-button text-danger" aria-label="Arsipkan staff {{ $staff->name }}"><i class="bi bi-archive" aria-hidden="true"></i></button></form>
                </div></td>
            </tr>
        @empty
            <tr><td colspan="7"><x-ui.empty-state title="Belum ada staff" description="Tambahkan akun guru, instruktur/coach, atau kepala sekolah." icon="bi-person-badge" /></td></tr>
        @endforelse
    </tbody>
</table></div></div>
<div class="mt-4">{{ $staffMembers->links() }}</div>
@endsection
