@extends('layouts.auth')

@section('title', 'Daftar Peserta RKDD')

@section('content')
    <main class="student-register-page">
        <section class="student-register-card" aria-labelledby="student-register-form-title">
            <a class="student-register-brand" href="{{ route('home') }}">
                <span class="brand-mark" aria-hidden="true">R</span>
                <span><strong>RKDD Cikampek Selatan</strong><small>Ruang Komunitas Digital Desa</small></span>
            </a>

            @php
                $draft = is_array($draft ?? null) ? $draft : null;
                $programBatches = $programBatches ?? collect();
                $draftProgram = $draft ? $programBatches->firstWhere('id', (int) ($draft['intended_program_batch_id'] ?? 0)) : null;
                $deviceOptions = ['android' => 'Ponsel Android', 'iphone' => 'iPhone', 'laptop' => 'Laptop', 'desktop' => 'Komputer desktop', 'shared' => 'Perangkat bersama', 'none' => 'Tidak punya perangkat pribadi'];
                $internetOptions = ['stable' => 'Stabil', 'limited' => 'Terbatas', 'mobile_data' => 'Paket data', 'none' => 'Tidak tersedia'];
                $skillOptions = ['design' => 'Desain grafis', 'photography' => 'Fotografi', 'video' => 'Videografi', 'presentation' => 'Presentasi', 'ai' => 'AI', 'coding' => 'Coding', 'data' => 'Data', 'entrepreneurship' => 'Kewirausahaan digital'];
                $oldArray = fn (string $key) => old($key, $draft[$key] ?? []);
            @endphp

            <div class="student-register-heading">
                <p class="skuad-eyebrow">Pendaftaran peserta</p>
                <h1 id="student-register-form-title">{{ $draft ? 'Profil sudah lengkap.' : 'Isi profil sebelum masuk Google.' }}</h1>
                <p>{{ $draft ? 'Lanjutkan dengan Google, lalu masukkan kode pendaftaran sesuai program yang dipilih.' : 'Pilih tujuan program, isi data profil, lalu tombol Google akan muncul setelah form tersimpan.' }}</p>
            </div>

            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if ($draft)
                <div class="student-register-preview">
                    <article>
                        <i class="bi bi-check-circle" aria-hidden="true"></i>
                        <div>
                            <strong>{{ $draft['name'] }}</strong>
                            <span>{{ $draftProgram ? $draftProgram->program->name.' · '.$draftProgram->institution->name.' · '.$draftProgram->period_label : 'Tujuan program akan mengikuti kode pendaftaran.' }}</span>
                        </div>
                    </article>
                    <article>
                        <i class="bi bi-key" aria-hidden="true"></i>
                        <div><strong>Kode tetap dari admin/pembina</strong><span>Pastikan kode yang dimasukkan sesuai dengan program tujuan.</span></div>
                    </article>
                </div>

                <a class="btn btn-skuad-google w-100 d-inline-flex align-items-center justify-content-center gap-2 mt-4" href="{{ route('google.redirect') }}">
                    <i class="bi bi-google" aria-hidden="true"></i>
                    Lanjut daftar dengan Google
                </a>

                <form method="POST" action="{{ route('student.register.reset') }}" class="mt-3">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-link p-0" type="submit">Ubah data form terlebih dahulu</button>
                </form>
            @else
                <form method="POST" action="{{ route('student.register.store') }}" class="student-register-form">
                    @csrf

                    <section class="student-register-section">
                        <h2>Tujuan program</h2>
                        <p>Pilih kegiatan yang ingin diikuti. Kode pendaftaran setelah Google harus sesuai dengan pilihan ini.</p>
                        <label class="form-label" for="intended_program_batch_id">Program yang dipilih</label>
                        <select class="form-select @error('intended_program_batch_id') is-invalid @enderror" id="intended_program_batch_id" name="intended_program_batch_id" @if($programBatches->isNotEmpty()) required @endif>
                            <option value="">{{ $programBatches->isNotEmpty() ? 'Pilih tujuan program' : 'Program aktif belum tersedia' }}</option>
                            @foreach($programBatches as $batch)
                                <option value="{{ $batch->id }}" @selected((int) old('intended_program_batch_id') === $batch->id)>
                                    {{ $batch->program->name }} · {{ $batch->institution->name }} · {{ $batch->period_label }}
                                </option>
                            @endforeach
                        </select>
                        @error('intended_program_batch_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </section>

                    <section class="student-register-section">
                        <h2>Identitas peserta</h2>
                        <div class="row g-3">
                            <div class="col-md-8"><label class="form-label" for="name">Nama lengkap</label><input class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required>@error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                            <div class="col-md-4"><label class="form-label" for="nickname">Nama panggilan</label><input class="form-control @error('nickname') is-invalid @enderror" id="nickname" name="nickname" value="{{ old('nickname') }}">@error('nickname')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                            <div class="col-md-6"><label class="form-label" for="student_number">Nomor induk siswa/peserta</label><input class="form-control @error('student_number') is-invalid @enderror" id="student_number" name="student_number" value="{{ old('student_number') }}" required>@error('student_number')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                            <div class="col-md-6"><label class="form-label" for="nisn">NISN <span class="text-secondary fw-normal">(opsional)</span></label><input class="form-control @error('nisn') is-invalid @enderror" id="nisn" name="nisn" inputmode="numeric" value="{{ old('nisn') }}">@error('nisn')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                            <div class="col-md-4"><label class="form-label" for="gender">Jenis kelamin</label><select class="form-select @error('gender') is-invalid @enderror" id="gender" name="gender" required><option value="">Pilih</option><option value="male" @selected(old('gender') === 'male')>Laki-laki</option><option value="female" @selected(old('gender') === 'female')>Perempuan</option></select>@error('gender')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                            <div class="col-md-4"><label class="form-label" for="birth_date">Tanggal lahir</label><input class="form-control @error('birth_date') is-invalid @enderror" id="birth_date" name="birth_date" type="date" value="{{ old('birth_date') }}" required>@error('birth_date')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                            <div class="col-md-4"><label class="form-label" for="grade_level">Tingkat saat ini</label><select class="form-select @error('grade_level') is-invalid @enderror" id="grade_level" name="grade_level" required><option value="">Pilih tingkat</option>@foreach([7, 8, 9] as $grade)<option value="{{ $grade }}" @selected((int) old('grade_level') === $grade)>Kelas {{ $grade }}</option>@endforeach</select>@error('grade_level')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                            <div class="col-12"><label class="form-label" for="school_class_name">Kelas sekolah asal / asal komunitas</label><input class="form-control @error('school_class_name') is-invalid @enderror" id="school_class_name" name="school_class_name" value="{{ old('school_class_name') }}" placeholder="Contoh: 7A, 8B, Karang Taruna, UMKM Ciksel" required>@error('school_class_name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                        </div>
                    </section>

                    <section class="student-register-section">
                        <h2>Orang tua/wali</h2>
                        <div class="row g-3">
                            <div class="col-md-7"><label class="form-label" for="parent_name">Nama orang tua/wali</label><input class="form-control @error('parent_name') is-invalid @enderror" id="parent_name" name="parent_name" value="{{ old('parent_name') }}" required>@error('parent_name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                            <div class="col-md-5"><label class="form-label" for="parent_phone">Nomor telepon</label><input class="form-control @error('parent_phone') is-invalid @enderror" id="parent_phone" name="parent_phone" inputmode="tel" value="{{ old('parent_phone') }}" required>@error('parent_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                            <div class="col-md-5"><label class="form-label" for="guardian_relationship">Hubungan</label><input class="form-control @error('guardian_relationship') is-invalid @enderror" id="guardian_relationship" name="guardian_relationship" value="{{ old('guardian_relationship') }}" placeholder="Contoh: Ibu" required>@error('guardian_relationship')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                            <div class="col-md-7"><label class="form-label" for="address">Alamat singkat <span class="text-secondary fw-normal">(opsional)</span></label><textarea class="form-control @error('address') is-invalid @enderror" id="address" name="address" rows="3">{{ old('address') }}</textarea>@error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                        </div>
                    </section>

                    <section class="student-register-section">
                        <h2>Perangkat dan internet</h2>
                        <fieldset><legend class="form-label">Perangkat yang dapat digunakan</legend><div class="student-register-options">@foreach($deviceOptions as $value => $label)<label><input type="checkbox" name="device_access[]" value="{{ $value }}" @checked(in_array($value, $oldArray('device_access'), true))><span>{{ $label }}</span></label>@endforeach</div>@error('device_access')<div class="text-danger small mt-2">{{ $message }}</div>@enderror</fieldset>
                        <div class="mt-3"><label class="form-label" for="internet_access">Akses internet</label><select class="form-select @error('internet_access') is-invalid @enderror" id="internet_access" name="internet_access" required><option value="">Pilih kondisi</option>@foreach($internetOptions as $value => $label)<option value="{{ $value }}" @selected(old('internet_access') === $value)>{{ $label }}</option>@endforeach</select>@error('internet_access')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                        <div class="form-check form-switch mt-3"><input type="hidden" name="willing_to_share_device" value="0"><input class="form-check-input" id="willing_to_share_device" name="willing_to_share_device" type="checkbox" value="1" @checked(old('willing_to_share_device'))><label class="form-check-label" for="willing_to_share_device">Bersedia menggunakan perangkat kelompok jika diperlukan</label></div>
                        <div class="mt-3"><label class="form-label" for="digital_apps_text">Aplikasi yang pernah digunakan</label><input class="form-control" id="digital_apps_text" name="digital_apps_text" value="{{ old('digital_apps_text') }}" placeholder="Canva, Google Docs, CapCut"><div class="form-text">Pisahkan dengan koma.</div></div>
                    </section>

                    <section class="student-register-section">
                        <h2>Minat dan target belajar</h2>
                        <fieldset><legend class="form-label">Bidang yang diminati</legend><div class="student-register-options">@foreach($skillOptions as $value => $label)<label><input type="checkbox" name="interests[]" value="{{ $value }}" @checked(in_array($value, $oldArray('interests'), true))><span>{{ $label }}</span></label>@endforeach</div>@error('interests')<div class="text-danger small mt-2">{{ $message }}</div>@enderror</fieldset>
                        <fieldset class="mt-3"><legend class="form-label">Kemampuan yang pernah dicoba</legend><div class="student-register-options">@foreach($skillOptions as $value => $label)<label><input type="checkbox" name="initial_skills[]" value="{{ $value }}" @checked(in_array($value, $oldArray('initial_skills'), true))><span>{{ $label }}</span></label>@endforeach</div></fieldset>
                        <div class="mt-3"><label class="form-label" for="experience">Pengalaman proyek <span class="text-secondary fw-normal">(opsional)</span></label><textarea class="form-control" id="experience" name="experience" rows="3">{{ old('experience') }}</textarea></div>
                        <div class="mt-3"><label class="form-label" for="expectation">Harapan mengikuti program</label><textarea class="form-control @error('expectation') is-invalid @enderror" id="expectation" name="expectation" rows="3" required>{{ old('expectation') }}</textarea>@error('expectation')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                        <div class="mt-3"><label class="form-label" for="learning_targets">Target keterampilan</label><textarea class="form-control @error('learning_targets') is-invalid @enderror" id="learning_targets" name="learning_targets" rows="3" required>{{ old('learning_targets') }}</textarea>@error('learning_targets')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    </section>

                    <button class="btn btn-skuad w-100 mt-2" type="submit">Simpan form, lalu tampilkan Google</button>
                </form>
            @endif

            <div class="skuad-auth-student-note">
                <i class="bi bi-shield-check" aria-hidden="true"></i>
                <p><strong>Aman dan bertahap</strong><small>Setelah Google, peserta tetap perlu memasukkan kode dari admin/pembina sesuai program tujuan.</small></p>
            </div>

            <a class="skuad-auth-back" href="{{ route('home') }}"><i class="bi bi-arrow-left"></i> Kembali ke beranda RKDD</a>
        </section>
    </main>
@endsection
