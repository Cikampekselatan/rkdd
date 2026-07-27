@extends('layouts.dashboard')

@section('title', 'Scan Kehadiran - SKUAD Learning Hub')
@section('breadcrumb', 'Scan kehadiran')

@section('content')
    <div class="attendance-page student-attendance-page">
        <section class="student-attendance-hero">
            <div>
                <p class="skuad-eyebrow">Presensi ruang kelas</p>
                <h1>Scan QR dari layar guru</h1>
                <p>Buka kamera dari halaman ini, arahkan ke QR yang ditampilkan guru/coach di ruang kelas, lalu konfirmasi check-in.</p>
            </div>
            <div class="student-attendance-score">
                <strong><i class="bi bi-qr-code-scan"></i></strong>
                <span>Scan langsung</span>
            </div>
        </section>

        <section class="attendance-section-card attendance-scanner-card" data-attendance-scanner data-check-in-path="/student/attendance/check-in/">
            <div class="attendance-section-heading">
                <div>
                    <p class="skuad-eyebrow">Kamera siswa</p>
                    <h2>Arahkan ke QR presensi</h2>
                </div>
                <span>QR hanya dari guru/coach</span>
            </div>

            <div class="attendance-scanner-frame">
                <video muted playsinline data-attendance-scanner-video></video>
                <div class="attendance-scanner-reticle" aria-hidden="true"></div>
                <div class="attendance-scanner-state" data-attendance-scanner-state>
                    <i class="bi bi-camera-video"></i>
                    <strong>Kamera belum aktif</strong>
                    <small>Tekan tombol mulai scan, lalu izinkan kamera.</small>
                </div>
            </div>

            <div class="attendance-scanner-actions">
                <button class="btn btn-primary skuad-touch-button" type="button" data-attendance-scanner-start>
                    <i class="bi bi-camera"></i> Mulai scan
                </button>
                <button class="btn btn-outline-secondary skuad-touch-button" type="button" data-attendance-scanner-stop disabled>
                    <i class="bi bi-stop-circle"></i> Berhenti
                </button>
                <a class="btn btn-outline-secondary skuad-touch-button" href="{{ route('student.attendance.index') }}">
                    <i class="bi bi-clock-history"></i> Riwayat
                </a>
            </div>

            <div class="alert alert-info mb-0">
                <strong>Catatan:</strong> kalau kamera tidak terbuka, biasanya browser meminta HTTPS. Gunakan Chrome terbaru di Android/iOS, atau minta guru membuka link presensi dari perangkat kelas.
            </div>
        </section>
    </div>
@endsection
