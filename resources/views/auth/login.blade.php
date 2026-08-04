@extends('layouts.auth')

@section('content')
    <main class="skuad-auth-shell">
        <section class="skuad-auth-story" aria-labelledby="auth-story-title">
            <a class="skuad-brand text-white" href="{{ route('home') }}">
                <span class="brand-mark" aria-hidden="true">S</span>
                <span class="skuad-brand-copy"><strong>SKUAD</strong><small>Learning Hub</small></span>
            </a>

            <div class="skuad-auth-story-copy">
                <p class="skuad-auth-kicker">Siswa Kreatif Update Digital</p>
                <h1 id="auth-story-title">Satu ruang untuk mendampingi karya dan pertumbuhan.</h1>
                <p>Kelola pembelajaran, kehadiran, tugas, penilaian, dan portofolio SKUAD dengan alur yang jelas.</p>
            </div>

            <div class="skuad-auth-metric">
                <span>30</span>
                <p><strong>Alur belajar fleksibel</strong><small>Modul dan pertemuan mengikuti kebutuhan tiap program.</small></p>
            </div>
        </section>

        <section class="skuad-auth-form-panel" aria-labelledby="login-title">
            <div class="skuad-auth-form-wrap">
                <a class="skuad-auth-mobile-brand" href="{{ route('home') }}">
                    <span class="brand-mark" aria-hidden="true">S</span>
                    <strong>SKUAD Learning Hub</strong>
                </a>

                <p class="skuad-eyebrow">Akses akun</p>
                <h2 id="login-title">Selamat datang kembali.</h2>
                <p class="skuad-auth-lead">Siswa tidak memakai password lokal. Siswa masuk kembali dengan akun Google yang sama, sedangkan email dan kata sandi di bawah ini khusus staff sekolah.</p>

                <a class="btn btn-skuad-google w-100 d-inline-flex align-items-center justify-content-center gap-2 mt-3" href="{{ route('google.redirect') }}">
                    <i class="bi bi-google" aria-hidden="true"></i>
                    Masuk / daftar siswa dengan Google
                </a>

                <div class="skuad-auth-student-note">
                    <i class="bi bi-shield-check" aria-hidden="true"></i>
                    <p><strong>Untuk siswa</strong><small>Jika sudah pernah aktif, tombol Google langsung membuka dashboard siswa. Jika baru, lanjutkan kode pendaftaran dan profil.</small></p>
                </div>

                <div class="skuad-auth-divider"><span>staff sekolah</span></div>

                @error('google')
                    <div class="alert alert-danger d-flex align-items-start gap-2 mt-4" role="alert">
                        <i class="bi bi-exclamation-circle-fill" aria-hidden="true"></i>
                        <span>{{ $message }}</span>
                    </div>
                @enderror

                <form method="POST" action="{{ route('login.store') }}" class="mt-4" data-auth-form>
                    @csrf

                    <div class="mb-3">
                        <label class="form-label" for="email">Email</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-envelope" aria-hidden="true"></i></span>
                            <input
                                class="form-control @error('email') is-invalid @enderror"
                                id="email"
                                name="email"
                                type="email"
                                value="{{ old('email') }}"
                                autocomplete="email"
                                autofocus
                                required
                            >
                        </div>
                        @error('email')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="password">Kata sandi</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock" aria-hidden="true"></i></span>
                            <input
                                class="form-control @error('password') is-invalid @enderror"
                                id="password"
                                name="password"
                                type="password"
                                autocomplete="current-password"
                                required
                            >
                            <button class="btn btn-outline-secondary" type="button" data-password-toggle="password" aria-label="Lihat kata sandi" aria-pressed="false">
                                <i class="bi bi-eye" aria-hidden="true"></i>
                            </button>
                        </div>
                        @error('password')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-flex align-items-center justify-content-between gap-3 mb-4">
                        <div class="form-check">
                            <input class="form-check-input" id="remember" name="remember" type="checkbox" value="1">
                            <label class="form-check-label" for="remember">Ingat saya</label>
                        </div>
                        <span class="small text-secondary">Akun dikelola admin</span>
                    </div>

                    <button class="btn btn-skuad-primary w-100 d-inline-flex align-items-center justify-content-center gap-2" type="submit" data-submit-button>
                        <span>Masuk ke dashboard</span>
                        <i class="bi bi-arrow-right" aria-hidden="true"></i>
                    </button>
                </form>

                <a class="skuad-auth-back" href="{{ route('home') }}"><i class="bi bi-arrow-left"></i> Kembali ke beranda</a>
            </div>
        </section>
    </main>
@endsection
