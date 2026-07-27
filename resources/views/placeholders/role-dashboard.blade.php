@extends('layouts.app')

@section('title', $role.' - SKUAD Learning Hub')

@section('content')
    <section class="placeholder-shell">
        <div class="container">
            <div class="placeholder-card mx-auto p-4 p-md-5" style="max-width: 44rem;">
                <p class="hero-kicker mb-2">Route placeholder</p>
                <h1 class="display-6 fw-bold mb-3">{{ $role }}</h1>
                <p class="lead text-secondary mb-4">{{ $description }}</p>
                <div class="alert alert-light border mb-4" role="status">
                    Route tersedia untuk memisahkan area role sejak fondasi aplikasi. Autentikasi dan otorisasi belum diterapkan pada Fase 1.
                </div>
                <a class="btn btn-skuad" href="{{ route('home') }}">Kembali ke beranda</a>
            </div>
        </div>
    </section>
@endsection
