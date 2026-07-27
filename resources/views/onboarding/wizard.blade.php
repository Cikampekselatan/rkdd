@extends('layouts.auth')

@section('title', $step->title().' - Onboarding SKUAD')

@section('content')
    @php
        $currentStep = $response?->current_step ?? 1;
        $onboardingBatch = $registrationCode?->programBatch;
        $onboardingProgram = $onboardingBatch?->program;
        $onboardingParticipantLabel = $onboardingBatch?->participant_label ?? 'Peserta';
        $onboardingAudienceIsSchool = ($onboardingBatch?->audience_type ?? 'school') === 'school';
        $preRegistration = is_array($preRegistration ?? null) ? $preRegistration : [];
        $deviceOptions = [
            'android' => ['bi-phone', 'Ponsel Android'],
            'iphone' => ['bi-phone', 'iPhone'],
            'laptop' => ['bi-laptop', 'Laptop'],
            'desktop' => ['bi-pc-display', 'Komputer desktop'],
            'shared' => ['bi-people', 'Perangkat bersama'],
            'none' => ['bi-slash-circle', 'Tidak punya perangkat pribadi'],
        ];
        $skillOptions = [
            'design' => ['bi-palette', 'Desain grafis'],
            'photography' => ['bi-camera', 'Fotografi'],
            'video' => ['bi-camera-reels', 'Videografi'],
            'presentation' => ['bi-easel', 'Presentasi'],
            'ai' => ['bi-stars', 'AI'],
            'coding' => ['bi-code-slash', 'Coding'],
            'data' => ['bi-bar-chart', 'Data'],
            'entrepreneurship' => ['bi-shop', 'Kewirausahaan digital'],
        ];
    @endphp

    <main class="onboarding-shell">
        <aside class="onboarding-aside">
            <a class="skuad-brand text-white" href="{{ route('home') }}">
                <span class="brand-mark" aria-hidden="true">S</span>
                <span class="skuad-brand-copy"><strong>SKUAD</strong><small>Learning Hub</small></span>
            </a>
            <div>
                <p class="skuad-auth-kicker">Profil {{ strtolower($onboardingParticipantLabel) }}</p>
                <h1>Kenali dirimu, lalu mulai berkarya di {{ $onboardingProgram?->name ?? 'program RKDD' }}.</h1>
                <p>Jawabanmu membantu pembina menyiapkan pengalaman belajar yang aman dan sesuai kebutuhan.</p>
            </div>
            <small>Data hanya digunakan untuk pembelajaran dan pendampingan {{ $onboardingProgram?->name ?? 'program RKDD' }}.</small>
        </aside>

        <section class="onboarding-main">
            <div class="onboarding-wrap">
                <div class="onboarding-mobile-brand">
                    <span class="brand-mark" aria-hidden="true">{{ str($onboardingProgram?->name ?? 'RKDD')->substr(0, 1)->upper() }}</span><strong>Onboarding {{ $onboardingProgram?->name ?? 'RKDD' }}</strong>
                </div>

                <div class="onboarding-progress" aria-label="Progress onboarding">
                    @foreach (\App\Enums\OnboardingStep::cases() as $wizardStep)
                        @php($accessible = $wizardStep->number() <= $currentStep)
                        <a @if ($accessible) href="{{ route('onboarding.wizard.show', $wizardStep->value) }}" @endif class="onboarding-progress-step {{ $wizardStep === $step ? 'active' : '' }} {{ $wizardStep->number() < $step->number() ? 'done' : '' }} {{ ! $accessible ? 'disabled' : '' }}">
                            <span>{{ $wizardStep->number() < $step->number() ? '✓' : $wizardStep->number() }}</span>
                            <small>{{ $wizardStep->title() }}</small>
                        </a>
                    @endforeach
                </div>

                <header class="onboarding-header">
                    <p class="skuad-eyebrow">Langkah {{ $step->number() }} dari 5</p>
                    <h2>{{ $step->title() }}</h2>
                    <p>
                        @switch($step)
                            @case(\App\Enums\OnboardingStep::Identity) Lengkapi data dasar sesuai kebutuhan administrasi {{ $onboardingProgram?->name ?? 'program' }}. @break
                            @case(\App\Enums\OnboardingStep::Guardian) Tambahkan kontak yang dapat dihubungi saat diperlukan. @break
                            @case(\App\Enums\OnboardingStep::Access) Ceritakan perangkat dan koneksi yang tersedia untuk belajar. @break
                            @case(\App\Enums\OnboardingStep::Interests) Pilih bidang yang membuatmu penasaran dan target yang ingin dicapai. @break
                            @case(\App\Enums\OnboardingStep::Agreements) Baca dan setujui aturan sebelum menjadi anggota aktif. @break
                        @endswitch
                    </p>
                </header>

                @error('onboarding')<div class="alert alert-danger">{{ $message }}</div>@enderror

                @switch($step)
                    @case(\App\Enums\OnboardingStep::Identity)
                        <form method="POST" action="{{ route('onboarding.wizard.identity.update') }}" data-onboarding-form>
                            @csrf @method('PUT')
                            <div class="row g-3">
                                <div class="col-md-8"><label class="form-label" for="name">Nama lengkap</label><input class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $profile?->user?->name ?? $preRegistration['name'] ?? auth()->user()->name) }}" required>@error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                                <div class="col-md-4"><label class="form-label" for="nickname">Nama panggilan</label><input class="form-control @error('nickname') is-invalid @enderror" id="nickname" name="nickname" value="{{ old('nickname', $profile?->nickname ?? $preRegistration['nickname'] ?? null) }}">@error('nickname')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                                <div class="col-md-6"><label class="form-label" for="student_number">Nomor induk/ID {{ strtolower($onboardingParticipantLabel) }}</label><input class="form-control @error('student_number') is-invalid @enderror" id="student_number" name="student_number" value="{{ old('student_number', $profile?->student_number ?? $preRegistration['student_number'] ?? null) }}" required>@error('student_number')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                                <div class="col-md-6"><label class="form-label" for="nisn">NISN <span class="fw-normal text-secondary">(opsional)</span></label><input class="form-control @error('nisn') is-invalid @enderror" id="nisn" name="nisn" inputmode="numeric" value="{{ old('nisn', $profile?->nisn ?? $preRegistration['nisn'] ?? null) }}">@error('nisn')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                                <div class="col-md-4"><label class="form-label" for="gender">Jenis kelamin</label><select class="form-select @error('gender') is-invalid @enderror" id="gender" name="gender" required><option value="">Pilih</option><option value="male" @selected(old('gender', $profile?->gender ?? $preRegistration['gender'] ?? null) === 'male')>Laki-laki</option><option value="female" @selected(old('gender', $profile?->gender ?? $preRegistration['gender'] ?? null) === 'female')>Perempuan</option></select>@error('gender')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                                <div class="col-md-4"><label class="form-label" for="birth_date">Tanggal lahir</label><input class="form-control @error('birth_date') is-invalid @enderror" id="birth_date" name="birth_date" type="date" value="{{ old('birth_date', $profile?->birth_date?->format('Y-m-d') ?? $preRegistration['birth_date'] ?? null) }}" required>@error('birth_date')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                                <div class="col-md-4"><label class="form-label" for="grade_level">{{ $onboardingAudienceIsSchool ? 'Tingkat saat ini' : 'Level/angkatan saat ini' }}</label><select class="form-select @error('grade_level') is-invalid @enderror" id="grade_level" name="grade_level" required><option value="">Pilih tingkat</option>@foreach([7, 8, 9] as $grade)<option value="{{ $grade }}" @selected((int) old('grade_level', $profile?->grade_level ?? $preRegistration['grade_level'] ?? 0) === $grade)>{{ $onboardingAudienceIsSchool ? 'Kelas '.$grade : 'Level '.$grade }}</option>@endforeach</select>@error('grade_level')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                                <div class="col-md-4"><label class="form-label" for="school_class_name">{{ $onboardingAudienceIsSchool ? 'Kelas sekolah asal' : 'Asal komunitas/kelas' }}</label><input class="form-control @error('school_class_name') is-invalid @enderror" id="school_class_name" name="school_class_name" value="{{ old('school_class_name', $profile?->school_class_name ?? $preRegistration['school_class_name'] ?? null) }}" placeholder="{{ $onboardingAudienceIsSchool ? 'Contoh: 7A' : 'Contoh: Karang Taruna/RKDD' }}" required>@error('school_class_name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                                <div class="col-md-4"><label class="form-label" for="class_id">Kelompok/Angkatan Program</label><select class="form-select @error('class_id') is-invalid @enderror" id="class_id" name="class_id" required><option value="">Pilih kelompok</option>@foreach($groups as $group)<option value="{{ $group->id }}" @selected((int) old('class_id', $registrationCode?->class_id ?? $profile?->class_id) === $group->id)>{{ $group->name }}</option>@endforeach</select><div class="form-text">Untuk program sekolah, anggota kelompok dapat berasal dari tingkat 7, 8, dan 9.</div>@if($groups->isEmpty())<div class="text-danger small mt-1">Kelompok/angkatan program periode ini belum tersedia. Hubungi pembina/admin.</div>@endif @error('class_id')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                            </div>
                            @include('onboarding._wizard-actions')
                        </form>
                        @break

                    @case(\App\Enums\OnboardingStep::Guardian)
                        <form method="POST" action="{{ route('onboarding.wizard.guardian.update') }}" data-onboarding-form>
                            @csrf @method('PUT')
                            <div class="row g-3">
                                <div class="col-md-7"><label class="form-label" for="parent_name">Nama orang tua/wali</label><input class="form-control @error('parent_name') is-invalid @enderror" id="parent_name" name="parent_name" value="{{ old('parent_name', $profile?->parent_name ?? $preRegistration['parent_name'] ?? null) }}" required>@error('parent_name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                                <div class="col-md-5"><label class="form-label" for="parent_phone">Nomor telepon</label><input class="form-control @error('parent_phone') is-invalid @enderror" id="parent_phone" name="parent_phone" inputmode="tel" value="{{ old('parent_phone', $profile?->parent_phone ?? $preRegistration['parent_phone'] ?? null) }}" required>@error('parent_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                                <div class="col-md-5"><label class="form-label" for="guardian_relationship">Hubungan dengan {{ strtolower($onboardingParticipantLabel) }}</label><input class="form-control @error('guardian_relationship') is-invalid @enderror" id="guardian_relationship" name="guardian_relationship" value="{{ old('guardian_relationship', $profile?->guardian_relationship ?? $preRegistration['guardian_relationship'] ?? null) }}" placeholder="Contoh: Ibu" required>@error('guardian_relationship')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                                <div class="col-md-7"><label class="form-label" for="address">Alamat singkat <span class="fw-normal text-secondary">(opsional)</span></label><textarea class="form-control @error('address') is-invalid @enderror" id="address" name="address" rows="3">{{ old('address', $profile?->address ?? $preRegistration['address'] ?? null) }}</textarea>@error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                            </div>
                            @include('onboarding._wizard-actions')
                        </form>
                        @break

                    @case(\App\Enums\OnboardingStep::Access)
                        <form method="POST" action="{{ route('onboarding.wizard.access.update') }}" data-onboarding-form>
                            @csrf @method('PUT')
                            <fieldset><legend class="form-label">Perangkat yang dapat digunakan</legend><div class="onboarding-choice-grid">@foreach($deviceOptions as $value => [$icon, $label])<label class="onboarding-choice"><input type="checkbox" name="device_access[]" value="{{ $value }}" @checked(in_array($value, old('device_access', $response?->device_access ?? $preRegistration['device_access'] ?? []), true))><span><i class="bi {{ $icon }}"></i>{{ $label }}</span></label>@endforeach</div>@error('device_access')<div class="text-danger small mt-2">{{ $message }}</div>@enderror</fieldset>
                            <div class="mt-4"><label class="form-label" for="internet_access">Akses internet</label><select class="form-select @error('internet_access') is-invalid @enderror" id="internet_access" name="internet_access" required><option value="">Pilih kondisi</option>@foreach(['stable'=>'Stabil','limited'=>'Terbatas','mobile_data'=>'Paket data','none'=>'Tidak tersedia'] as $value=>$label)<option value="{{ $value }}" @selected(old('internet_access', $response?->internet_access ?? $preRegistration['internet_access'] ?? null) === $value)>{{ $label }}</option>@endforeach</select>@error('internet_access')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                            <div class="form-check form-switch mt-4"><input type="hidden" name="willing_to_share_device" value="0"><input class="form-check-input" id="willing_to_share_device" name="willing_to_share_device" type="checkbox" value="1" @checked(old('willing_to_share_device', $response?->willing_to_share_device ?? $preRegistration['willing_to_share_device'] ?? false))><label class="form-check-label" for="willing_to_share_device">Bersedia menggunakan perangkat kelompok jika diperlukan</label></div>
                            <div class="mt-4"><label class="form-label" for="digital_apps_text">Aplikasi yang pernah digunakan</label><input class="form-control" id="digital_apps_text" name="digital_apps_text" value="{{ old('digital_apps_text', implode(', ', $response?->digital_apps ?? $preRegistration['digital_apps'] ?? [])) }}" placeholder="Canva, Google Docs, CapCut"><div class="form-text">Pisahkan dengan koma.</div></div>
                            @include('onboarding._wizard-actions')
                        </form>
                        @break

                    @case(\App\Enums\OnboardingStep::Interests)
                        <form method="POST" action="{{ route('onboarding.wizard.interests.update') }}" data-onboarding-form>
                            @csrf @method('PUT')
                            <fieldset><legend class="form-label">Bidang yang diminati</legend><div class="onboarding-choice-grid">@foreach($skillOptions as $value => [$icon, $label])<label class="onboarding-choice"><input type="checkbox" name="interests[]" value="{{ $value }}" @checked(in_array($value, old('interests', $response?->interests ?? $preRegistration['interests'] ?? []), true))><span><i class="bi {{ $icon }}"></i>{{ $label }}</span></label>@endforeach</div>@error('interests')<div class="text-danger small mt-2">{{ $message }}</div>@enderror</fieldset>
                            <fieldset class="mt-4"><legend class="form-label">Kemampuan yang sudah pernah dicoba</legend><div class="onboarding-choice-grid">@foreach($skillOptions as $value => [$icon, $label])<label class="onboarding-choice"><input type="checkbox" name="initial_skills[]" value="{{ $value }}" @checked(in_array($value, old('initial_skills', $response?->initial_skills ?? $preRegistration['initial_skills'] ?? []), true))><span><i class="bi {{ $icon }}"></i>{{ $label }}</span></label>@endforeach</div></fieldset>
                            <div class="mt-4"><label class="form-label" for="experience">Pengalaman proyek <span class="fw-normal text-secondary">(opsional)</span></label><textarea class="form-control" id="experience" name="experience" rows="3">{{ old('experience', $response?->experience ?? $preRegistration['experience'] ?? null) }}</textarea></div>
                            <div class="mt-3"><label class="form-label" for="expectation">Harapan mengikuti {{ $onboardingProgram?->name ?? 'program ini' }}</label><textarea class="form-control @error('expectation') is-invalid @enderror" id="expectation" name="expectation" rows="3" required>{{ old('expectation', $response?->expectation ?? $preRegistration['expectation'] ?? null) }}</textarea>@error('expectation')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                            <div class="mt-3"><label class="form-label" for="learning_targets">Target keterampilan</label><textarea class="form-control @error('learning_targets') is-invalid @enderror" id="learning_targets" name="learning_targets" rows="3" required>{{ old('learning_targets', $response?->learning_targets ?? $preRegistration['learning_targets'] ?? null) }}</textarea>@error('learning_targets')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                            @include('onboarding._wizard-actions')
                        </form>
                        @break

                    @case(\App\Enums\OnboardingStep::Agreements)
                        <form method="POST" action="{{ route('onboarding.wizard.agreements.finalize') }}" data-onboarding-form>
                            @csrf
                            <div class="onboarding-agreements">
                                @foreach($agreementRules as $name => $rule)
                                    <div class="onboarding-agreement-card">
                                        <input id="{{ $name }}" type="checkbox" name="{{ $name }}" value="1" @checked(old($name))>
                                        <label for="{{ $name }}">
                                            <i class="bi {{ $rule['icon'] }}" aria-hidden="true"></i>
                                            <span>
                                                <strong>{{ $rule['title'] }}</strong>
                                                <small>Saya sudah membaca, memahami, dan menyetujuinya.</small>
                                            </span>
                                        </label>
                                        <button class="onboarding-rule-trigger" type="button" data-bs-toggle="modal" data-bs-target="#agreementRuleModal{{ $loop->iteration }}" aria-label="Baca {{ $rule['title'] }}">
                                            Baca aturan <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                    @error($name)<div class="text-danger small">{{ $message }}</div>@enderror
                                @endforeach
                            </div>
                            @include('onboarding._wizard-actions', ['finalStep' => true])
                        </form>

                        @foreach($agreementRules as $rule)
                            <div class="modal fade skuad-modal" id="agreementRuleModal{{ $loop->iteration }}" tabindex="-1" aria-labelledby="agreementRuleModal{{ $loop->iteration }}Label" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <div>
                                                <p class="skuad-eyebrow mb-1">Aturan siswa SKUAD</p>
                                                <h2 class="modal-title h4 fw-bold" id="agreementRuleModal{{ $loop->iteration }}Label">{{ $rule['title'] }}</h2>
                                            </div>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p class="text-secondary">{{ $rule['summary'] }}</p>
                                            <div class="agreement-rule-sections">
                                                @foreach($rule['sections'] as $section)
                                                    <article>
                                                        <h3>{{ $section['heading'] }}</h3>
                                                        <p>{{ $section['body'] }}</p>
                                                    </article>
                                                @endforeach
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-skuad" data-bs-dismiss="modal">Saya mengerti</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                        @break
                @endswitch
            </div>
        </section>
    </main>
@endsection
