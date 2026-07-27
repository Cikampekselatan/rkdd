@extends('layouts.auth')

@section('title', 'Akun Google Terhubung - SKUAD Learning Hub')

@section('content')
    <main class="skuad-auth-message-shell">
        <section class="skuad-auth-message-card">
            <span class="skuad-auth-message-icon"><i class="bi bi-google"></i></span>
            <p class="skuad-eyebrow">Google berhasil terhubung</p>
            <h1>Selamat datang, {{ auth()->user()->name }}.</h1>
            <p>Akunmu berstatus onboarding. Masukkan kode pendaftaran dari pembina sebelum melengkapi profil siswa.</p>
            <div class="skuad-auth-progress-list">
                <span class="done"><i class="bi bi-check-lg"></i><strong>Akun Google</strong></span>
                <span><i class="bi bi-2-circle"></i><strong>Kode pendaftaran</strong></span>
                <span><i class="bi bi-3-circle"></i><strong>Profil siswa</strong></span>
            </div>
            <div class="d-grid gap-2">
                <x-ui.button :href="route('onboarding.registration-code.show')" icon="bi-arrow-right">Masukkan kode pendaftaran</x-ui.button>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-ui.button class="w-100" type="submit" variant="ghost" icon="bi-box-arrow-right">Keluar sementara</x-ui.button>
                </form>
            </div>
        </section>
    </main>
@endsection
