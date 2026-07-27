@extends('layouts.app')

@section('title', 'Karya Terbaik RKDD')

@section('content')
<div class="landing-page public-dashboard rkdd-best-work-page">
    <section class="landing-hero public-hero rkdd-inner-hero">
        <div class="container">
            <p class="landing-kicker"><span></span> Karya Terbaik</p>
            <h1>Panggung apresiasi untuk karya peserta RKDD.</h1>
            <p class="landing-lead">Karya pilihan dari berbagai program kegiatan. Dipilih oleh instruktur/coach dan dikurasi agar menjadi motivasi belajar bagi peserta lain.</p>
            <div class="landing-trust"><div><strong>{{ $weeklyCount }}</strong><span>Minggu ini</span></div><div><strong>{{ $monthlyCount }}</strong><span>Bulan ini</span></div><div><strong>{{ $highlights->total() }}</strong><span>Karya tampil</span></div></div>
        </div>
    </section>

    <section class="public-highlights">
        <div class="container">
            <div class="public-highlight-grid">
                @forelse($highlights as $highlight)
                    @include('showcase-highlights._public-card', ['highlight' => $highlight])
                @empty
                    <article class="public-highlight-empty"><i class="bi bi-stars"></i><h3>Karya terbaik segera hadir.</h3><p>Instruktur/coach dan Super Admin dapat menambahkan karya pilihan dari dashboard.</p></article>
                @endforelse
            </div>
            <div class="mt-4">{{ $highlights->links() }}</div>
        </div>
    </section>
</div>
@endsection
