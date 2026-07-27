<div class="row g-3">
    @php
        $oldProgramBatchId = old('program_batch_id');
        $selectedProgramBatchId = $oldProgramBatchId !== null && $oldProgramBatchId !== ''
            ? (int) $oldProgramBatchId
            : (int) ($schoolClass->program_batch_id ?? $activeBatchId ?? 0);
    @endphp
    <div class="col-12">
        <label class="form-label" for="program_batch_id">Program/Periode tujuan</label>
        <select class="form-select @error('program_batch_id') is-invalid @enderror" id="program_batch_id" name="program_batch_id" required>
            <option value="">Pilih program/periode</option>
            @foreach($availableBatches as $batch)
                <option value="{{ $batch->id }}" @selected($selectedProgramBatchId === $batch->id)>
                    {{ $batch->program?->name }} · {{ $batch->institution?->name }} · {{ $batch->period_label }}
                </option>
            @endforeach
        </select>
        @error('program_batch_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <div class="form-text">Pilih program yang akan memiliki kelompok ini. Koordinator di bawah hanya berisi akun guru/coach, bukan siswa.</div>
    </div>

    @if($activeBatch)
        <div class="col-12">
            <div class="alert alert-info mb-2">
                <strong>Program aktif header:</strong> {{ $activeBatch->program?->name }} · {{ $activeBatch->institution?->name }} · {{ $activeBatch->period_label }}.
                Jika ingin membuat kelompok untuk program lain, pilih programnya pada kolom “Program/Periode tujuan” di atas.
            </div>
        </div>
    @endif
    <div class="col-md-6">
        <label class="form-label" for="academic_year_id">{{ $periodLabel ?? 'Periode/Tahun Ajaran' }}</label>
        <select class="form-select @error('academic_year_id') is-invalid @enderror" id="academic_year_id" name="academic_year_id" required>
            <option value="">Pilih tahun ajaran</option>
            @foreach($academicYears as $year)
                <option value="{{ $year->id }}" @selected((string) old('academic_year_id', $schoolClass->academic_year_id ?? '') === (string) $year->id)>{{ $year->name }}{{ $year->is_active ? ' - Aktif' : '' }}</option>
            @endforeach
        </select>
        @error('academic_year_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <div class="form-text">Satu periode program hanya memiliki satu {{ strtolower($groupLabel ?? 'kelompok/angkatan') }}. Program lain tetap bisa memiliki kelompok sendiri pada tahun ajaran yang sama.</div>
    </div>
    <div class="col-md-6">
        <label class="form-label" for="name">Nama kelompok</label>
        <input class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $schoolClass->name ?? '') }}" placeholder="{{ $activeBatch?->program?->name ? $activeBatch->program->name.' '.$activeBatch->period_label : 'Kelompok/Angkatan 2026/2027' }}" required>
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label" for="code">Kode kelompok</label>
        <input class="form-control @error('code') is-invalid @enderror" id="code" name="code" value="{{ old('code', $schoolClass->code ?? '') }}" placeholder="{{ $activeBatch?->program?->slug ? strtoupper(str_replace('-', '', $activeBatch->program->slug)).'-2026' : 'PROGRAM-2026' }}" required>
        @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label" for="capacity">Kapasitas anggota</label>
        <input class="form-control @error('capacity') is-invalid @enderror" id="capacity" name="capacity" type="number" min="1" max="300" value="{{ old('capacity', $schoolClass->capacity ?? 100) }}">
        @error('capacity')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label" for="homeroom_teacher_id">Koordinator kelompok</label>
        <select class="form-select @error('homeroom_teacher_id') is-invalid @enderror" id="homeroom_teacher_id" name="homeroom_teacher_id">
            <option value="">Belum ditentukan</option>
            @foreach($teachers as $teacher)
                <option value="{{ $teacher->id }}" @selected((string) old('homeroom_teacher_id', $schoolClass->homeroom_teacher_id ?? '') === (string) $teacher->id)>{{ $teacher->name }}</option>
            @endforeach
        </select>
        @error('homeroom_teacher_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-12">
        <input type="hidden" name="is_active" value="0">
        <div class="form-check form-switch"><input class="form-check-input" id="is_active" name="is_active" type="checkbox" value="1" @checked(old('is_active', $schoolClass->is_active ?? true))><label class="form-check-label" for="is_active">Kelompok aktif</label></div>
    </div>
</div>
<div class="d-flex justify-content-end gap-2 mt-4"><x-ui.button :href="route('admin.classes.index')" variant="ghost">Batal</x-ui.button><x-ui.button type="submit">Simpan kelompok</x-ui.button></div>
