@extends('layouts.auth')

@section('title', 'Kode Valid - SKUAD Learning Hub')

@section('content')
    <main class="skuad-auth-message-shell">
        <section class="skuad-auth-message-card">
            <span class="skuad-auth-message-icon"><i class="bi bi-check2-circle"></i></span>
            <p class="skuad-eyebrow">Kode terverifikasi</p>
            <h1>Kamu siap melanjutkan.</h1>
            <p>Kode pendaftaran valid dan tersimpan aman untuk akunmu. Jika form pra-daftar sudah lengkap, datanya otomatis dipakai. Kamu tinggal melengkapi bagian yang belum bisa ditentukan sebelum kode, lalu menyetujui aturan akhir.</p>
            <div class="skuad-auth-progress-list">
                <span class="done"><i class="bi bi-check-lg"></i><strong>Akun Google</strong></span>
                <span class="done"><i class="bi bi-check-lg"></i><strong>Kode pendaftaran</strong></span>
                <span><i class="bi bi-3-circle"></i><strong>{{ ($nextStep ?? 'identity') === 'agreements' ? 'Persetujuan akhir' : 'Profil siswa' }}</strong></span>
            </div>
            <x-ui.button :href="route('onboarding.wizard.show', $nextStep ?? 'identity')" icon="bi-arrow-right">{{ ($nextStep ?? 'identity') === 'agreements' ? 'Lanjut persetujuan akhir' : 'Mulai isi profil' }}</x-ui.button>
        </section>
    </main>
@endsection
