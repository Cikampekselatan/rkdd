@extends('layouts.auth')

@section('title', 'Domain Email Tidak Diizinkan - SKUAD Learning Hub')

@section('content')
    <main class="skuad-auth-message-shell">
        <section class="skuad-auth-message-card">
            <span class="skuad-auth-message-icon is-danger"><i class="bi bi-envelope-x"></i></span>
            <p class="skuad-eyebrow">Akses ditolak</p>
            <h1>Domain email belum diizinkan.</h1>
            <p>Email <strong>{{ $email }}</strong> menggunakan domain <strong>{{ $domain ?: 'tidak valid' }}</strong>.</p>
            <div class="skuad-auth-domain-list">
                <small>Domain yang dapat digunakan</small>
                @foreach ($allowedDomains as $allowedDomain)
                    <x-ui.badge variant="success" icon="bi-check-circle-fill">{{ $allowedDomain }}</x-ui.badge>
                @endforeach
            </div>
            <p class="small text-secondary">Gunakan akun Google yang sesuai atau hubungi pembina SKUAD jika sekolah menggunakan domain Google Workspace lain.</p>
            <x-ui.button :href="route('login')" variant="outline" icon="bi-arrow-left">Kembali ke halaman masuk</x-ui.button>
        </section>
    </main>
@endsection
