@php($staffModel = $staff ?? null)
@php($editing = $staffModel !== null)
@php($profile = $staffModel?->teacherProfile)
@php($currentRole = $staffModel?->roles->first()?->slug?->value ?? 'teacher')
@php($selectedProgramBatchIds = collect(old('program_batch_ids', $assignedProgramBatchIds ?? []))->map(fn($id) => (int) $id)->all())

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label" for="name">Nama lengkap</label>
        <input class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $staffModel?->name ?? '') }}" required>
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label class="form-label" for="email">Email</label>
        <input class="form-control @error('email') is-invalid @enderror" id="email" name="email" type="email" value="{{ old('email', $staffModel?->email ?? '') }}" required>
        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <div class="form-text">Email harus unik. Jika email sudah dipakai super admin/staff/siswa, gunakan menu edit akun terkait.</div>
    </div>

    <div class="col-md-6">
        <label class="form-label" for="password">Kata sandi {{ $editing ? 'baru (opsional)' : '' }}</label>
        <div class="input-group">
            <input class="form-control @error('password') is-invalid @enderror" id="password" name="password" type="password" autocomplete="new-password" {{ $editing ? '' : 'required' }}>
            <button class="btn btn-outline-secondary" type="button" data-password-toggle="password" aria-label="Lihat kata sandi" aria-pressed="false" title="Lihat kata sandi">
                <i class="bi bi-eye" aria-hidden="true"></i>
            </button>
        </div>
        @error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        <div class="form-text">Minimal 12 karakter, wajib ada huruf dan angka.</div>
    </div>

    <div class="col-md-6">
        <label class="form-label" for="password_confirmation">Konfirmasi kata sandi</label>
        <div class="input-group">
            <input class="form-control" id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" {{ $editing ? '' : 'required' }}>
            <button class="btn btn-outline-secondary" type="button" data-password-toggle="password_confirmation" aria-label="Lihat konfirmasi kata sandi" aria-pressed="false" title="Lihat konfirmasi kata sandi">
                <i class="bi bi-eye" aria-hidden="true"></i>
            </button>
        </div>
        <div class="form-text">Isi sama persis dengan kata sandi di kiri.</div>
    </div>

    <div class="col-md-6">
        <label class="form-label" for="role">Role</label>
        <select class="form-select @error('role') is-invalid @enderror" id="role" name="role">
            @foreach([\App\Enums\RoleSlug::Admin, \App\Enums\RoleSlug::Teacher, \App\Enums\RoleSlug::Coach, \App\Enums\RoleSlug::Principal] as $role)
                <option value="{{ $role->value }}" @selected(old('role', $currentRole) === $role->value)>{{ $role->label() }}</option>
            @endforeach
        </select>
        @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12">
        <label class="form-label">Penempatan program</label>
        <div class="skuad-card p-3 bg-light border-0 @error('program_batch_ids') border border-danger @enderror">
            @forelse($programBatches ?? [] as $batch)
                <div class="form-check py-2">
                    <input
                        class="form-check-input"
                        id="program_batch_{{ $batch->id }}"
                        name="program_batch_ids[]"
                        type="checkbox"
                        value="{{ $batch->id }}"
                        @checked(in_array($batch->id, $selectedProgramBatchIds, true))
                    >
                    <label class="form-check-label" for="program_batch_{{ $batch->id }}">
                        <strong>{{ $batch->program?->name }} — {{ $batch->institution?->name }}</strong>
                        <span class="d-block small text-secondary">{{ $batch->name }} · {{ $batch->period_label }} · {{ $batch->participant_label }}</span>
                    </label>
                </div>
            @empty
                <div class="text-secondary small">Belum ada program aktif. Buat program dan periode program terlebih dahulu.</div>
            @endforelse
        </div>
        @error('program_batch_ids')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        @error('program_batch_ids.*')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        <div class="form-text">Staff hanya akan melihat data dari program yang dicentang di sini.</div>
        @if(($programsWithoutActiveBatches ?? collect())->isNotEmpty())
            <div class="alert alert-warning mt-3 mb-0">
                <div class="fw-semibold mb-1">Ada program aktif yang belum muncul karena belum punya periode program aktif.</div>
                <div class="small mb-2">Buat periode terlebih dahulu, lalu kembali ke form staff untuk mencentang program tersebut.</div>
                <div class="d-flex flex-wrap gap-2">
                    @foreach($programsWithoutActiveBatches as $programWithoutBatch)
                        <a class="btn btn-sm btn-outline-dark" href="{{ route('super-admin.program-batches.create', ['program_id' => $programWithoutBatch->id]) }}">
                            Buat periode {{ $programWithoutBatch->name }}
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    <div class="col-md-6">
        <label class="form-label" for="employee_number">Nomor pegawai</label>
        <input class="form-control @error('employee_number') is-invalid @enderror" id="employee_number" name="employee_number" value="{{ old('employee_number', $profile?->employee_number) }}">
        @error('employee_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label class="form-label" for="phone">Telepon</label>
        <input class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone', $profile?->phone) }}">
        @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label class="form-label" for="specialization">Spesialisasi</label>
        <input class="form-control @error('specialization') is-invalid @enderror" id="specialization" name="specialization" value="{{ old('specialization', $profile?->specialization) }}">
        @error('specialization')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12">
        <label class="form-label" for="bio">Bio singkat</label>
        <textarea class="form-control @error('bio') is-invalid @enderror" id="bio" name="bio" rows="3">{{ old('bio', $profile?->bio) }}</textarea>
        @error('bio')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12">
        <input type="hidden" name="is_active" value="0">
        <div class="form-check form-switch">
            <input class="form-check-input" id="is_active" name="is_active" type="checkbox" value="1" @checked(old('is_active', $profile?->is_active ?? true))>
            <label class="form-check-label" for="is_active">Akun aktif</label>
        </div>
    </div>
</div>

<div class="d-flex justify-content-end gap-2 mt-4">
    <x-ui.button :href="route('admin.staff.index')" variant="ghost">Batal</x-ui.button>
    <x-ui.button type="submit">Simpan staff</x-ui.button>
</div>
