@php($editing = isset($registrationCode))

<div class="row g-3">
    <div class="col-12">
        <label class="form-label" for="name">Nama/gelombang kode</label>
        <input class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $registrationCode->name ?? '') }}" required>
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label" for="academic_year_id">Periode/Tahun Ajaran</label>
        <select class="form-select @error('academic_year_id') is-invalid @enderror" id="academic_year_id" name="academic_year_id" required><option value="">Pilih tahun ajaran</option>@foreach($academicYears as $academicYear)<option value="{{ $academicYear->id }}" @selected((string) old('academic_year_id', $registrationCode->academic_year_id ?? '') === (string) $academicYear->id)>{{ $academicYear->name }}{{ $academicYear->is_active ? ' • Aktif' : '' }}</option>@endforeach</select>
        @error('academic_year_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label" for="class_id">Kelompok/Angkatan <span class="text-secondary fw-normal">(opsional)</span></label>
        <select class="form-select @error('class_id') is-invalid @enderror" id="class_id" name="class_id"><option value="">Semua kelas</option>@foreach($classes as $class)<option value="{{ $class->id }}" @selected((string) old('class_id', $registrationCode->class_id ?? '') === (string) $class->id)>{{ $class->name }} — {{ $class->academicYear->name }}</option>@endforeach</select>
        <div class="form-text">Kosongkan agar kode berlaku untuk semua kelas.</div>
        @error('class_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label" for="max_uses">Batas penggunaan</label>
        <input class="form-control @error('max_uses') is-invalid @enderror" id="max_uses" name="max_uses" type="number" min="1" max="10000" value="{{ old('max_uses', $registrationCode->max_uses ?? '') }}" placeholder="Tanpa batas">
        @error('max_uses')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label" for="starts_at">Mulai berlaku</label>
        <input class="form-control @error('starts_at') is-invalid @enderror" id="starts_at" name="starts_at" type="datetime-local" value="{{ old('starts_at', isset($registrationCode) && $registrationCode->starts_at ? $registrationCode->starts_at->format('Y-m-d\TH:i') : '') }}">
        @error('starts_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label" for="expires_at">Kedaluwarsa</label>
        <input class="form-control @error('expires_at') is-invalid @enderror" id="expires_at" name="expires_at" type="datetime-local" value="{{ old('expires_at', isset($registrationCode) && $registrationCode->expires_at ? $registrationCode->expires_at->format('Y-m-d\TH:i') : '') }}">
        @error('expires_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-12">
        <div class="form-check form-switch">
            <input type="hidden" name="is_active" value="0">
            <input class="form-check-input" id="is_active" name="is_active" type="checkbox" value="1" @checked(old('is_active', $registrationCode->is_active ?? true))>
            <label class="form-check-label fw-semibold" for="is_active">Kode aktif</label>
        </div>
    </div>
</div>

<div class="d-flex flex-column-reverse flex-sm-row justify-content-end gap-2 mt-4">
    <x-ui.button :href="route('admin.registration-codes.index')" variant="ghost">Batal</x-ui.button>
    <x-ui.button type="submit" icon="bi-check-lg">{{ $editing ? 'Simpan perubahan' : 'Buat kode aman' }}</x-ui.button>
</div>
