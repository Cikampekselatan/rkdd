@extends('layouts.dashboard')

@section('title', ($group->exists ? 'Edit' : 'Buat').' Kelompok Proyek - SKUAD Learning Hub')
@section('breadcrumb', 'Kelompok Proyek')

@section('content')
    <div class="assignment-page">
        <x-ui.page-header
            eyebrow="Kolaborasi"
            :title="$group->exists ? 'Edit kelompok proyek' : 'Buat kelompok proyek'"
            :description="'Program aktif: '.($activeBatch?->program?->name ?? 'Program RKDD').' · '.($activeBatch?->institution?->name ?? 'RKDD Cikampek Selatan').' · '.($activeBatch?->period_label ?? 'periode aktif').'. Pilih kelompok peserta asal dan '.$participantLabel.' aktif yang tergabung dalam tim proyek.'"
        />

        <form class="card border-0 shadow-sm p-4" method="POST" action="{{ $group->exists ? route('teacher.project-groups.update', $group) : route('teacher.project-groups.store') }}">
            @csrf
            @if($group->exists)
                @method('PUT')
            @endif

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label" for="academic_year_id">Periode/Tahun Ajaran</label>
                    <select class="form-select @error('academic_year_id') is-invalid @enderror" id="academic_year_id" name="academic_year_id" required>
                        <option value="">Pilih tahun ajaran</option>
                        @foreach($academicYears as $year)
                            <option value="{{ $year->id }}" @selected(old('academic_year_id', $group->academic_year_id) == $year->id)>{{ $year->name }}</option>
                        @endforeach
                    </select>
                    @error('academic_year_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label" for="class_id">Kelompok peserta asal</label>
                    <select class="form-select @error('class_id') is-invalid @enderror" id="class_id" name="class_id" required>
                        <option value="">Pilih {{ strtolower($groupLabel) }} {{ $activeBatch?->program?->name ?? 'program aktif' }}</option>
                        @foreach($classes as $class)
                            <option value="{{ $class->id }}" @selected(old('class_id', $group->class_id) == $class->id)>{{ $class->name }} · {{ $class->academicYear->name }}</option>
                        @endforeach
                    </select>
                    <div class="form-text">Ini bukan nama tim proyek, tetapi kelompok/batch asal {{ strtolower($participantLabel) }} pada program aktif.</div>
                    @error('class_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label" for="status">Status</label>
                    <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                        @foreach(['active' => 'Aktif', 'completed' => 'Selesai', 'archived' => 'Arsip'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('status', $group->status ?: 'active') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="name">Nama kelompok</label>
                    <input class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $group->name) }}" required placeholder="Contoh: Tim Garuda Digital">
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    @php($selectedMembers = collect(old('member_ids', $group->exists ? $group->members->pluck('user_id')->all() : []))->map(fn($id) => (int) $id)->all())
                    <label class="form-label" for="member_ids">Anggota {{ strtolower($participantLabel) }}</label>
                    <select class="form-select @error('member_ids') is-invalid @enderror" id="member_ids" name="member_ids[]" multiple required size="8">
                        @foreach($students as $student)
                            <option value="{{ $student->id }}" @selected(in_array($student->id, $selectedMembers, true))>
                                {{ $student->name }} · Kelas {{ $student->studentProfile?->grade_level ?? '-' }} · {{ $student->studentProfile?->schoolClass?->name ?? 'Tanpa kelompok' }}
                            </option>
                        @endforeach
                    </select>
                    <div class="form-text">Gunakan Ctrl/Command untuk memilih beberapa {{ strtolower($participantLabel) }}.</div>
                    @error('member_ids')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    @error('member_ids.*')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>

                <div class="col-12">
                    <label class="form-label" for="description">Deskripsi</label>
                    <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="4" placeholder="Tujuan, tema, atau peran umum kelompok.">{{ old('description', $group->description) }}</textarea>
                    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-4">
                <a class="btn btn-outline-secondary" href="{{ route('teacher.project-groups.index') }}">Batal</a>
                <button class="btn btn-primary">{{ $group->exists ? 'Simpan perubahan' : 'Buat kelompok' }}</button>
            </div>
        </form>
    </div>
@endsection
