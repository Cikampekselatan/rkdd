@extends('layouts.app')

@section('title', 'Akses Ditolak - SKUAD Learning Hub')

@section('content')
<section class="placeholder-shell">
    <div class="container">
        <div class="placeholder-card mx-auto p-4 p-md-5 text-center" style="max-width: 42rem">
            <span class="skuad-empty-icon"><i class="bi bi-shield-lock"></i></span>
            <p class="hero-kicker">403 · Access denied</p>
            <h1 class="display-6 fw-bold">Akses ditolak</h1>
            <p class="lead text-secondary">Akun Anda belum memiliki izin untuk membuka halaman ini, atau halaman ini hanya tersedia untuk peran tertentu.</p>
            <a class="btn btn-skuad" href="{{ auth()->check() ? route(auth()->user()->dashboardRouteName()) : route('home') }}">Kembali ke dashboard</a>
        </div>
    </div>
</section>
@endsection
