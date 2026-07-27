@extends('layouts.dashboard')

@section('title', 'Check-in Presensi - SKUAD Learning Hub')
@section('breadcrumb', 'Check-in presensi')

@section('content')
    <div class="attendance-page student-attendance-page">
        <section class="student-attendance-hero">
            <div>
                <p class="skuad-eyebrow">Presensi mandiri</p>
                <h1>Check-in SKUAD</h1>
                <p>Pastikan detail pertemuan benar sebelum menekan tombol hadir.</p>
            </div>
            <div class="student-attendance-score">
                <strong><i class="bi bi-qr-code-scan"></i></strong>
                <span>{{ $attendanceSession->status->label() }}</span>
            </div>
        </section>

        @if (session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
        @if ($error)<div class="alert alert-danger" role="alert">{{ $error }}</div>@endif
        @if ($errors->any())<div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>@endif

        <section class="attendance-section-card">
            <div class="attendance-section-heading">
                <div>
                    <p class="skuad-eyebrow">Pertemuan {{ $attendanceSession->learningSession->session_number }}</p>
                    <h2>{{ $attendanceSession->learningSession->title }}</h2>
                </div>
                <span>{{ $attendanceSession->schoolClass->name }}</span>
            </div>

            <div class="student-attendance-history">
                <article>
                    <span class="student-attendance-number"><i class="bi bi-calendar2-check"></i></span>
                    <div>
                        <small>{{ $attendanceSession->attendance_date->translatedFormat('l, d F Y') }}</small>
                        <h3>Status check-in kamu</h3>
                        <p>
                            @if ($record?->checked_in_at)
                                Sudah check-in pada {{ $record->checked_in_at->translatedFormat('H:i') }} WIB.
                            @else
                                Belum check-in untuk sesi ini.
                            @endif
                        </p>
                    </div>
                    @if ($record)
                        <span class="attendance-final-status attendance-tone-{{ $record->status->value }}"><i class="bi {{ $record->status->icon() }}"></i> {{ $record->status->label() }}</span>
                    @endif
                </article>
            </div>

            <div class="mt-4 d-flex flex-wrap gap-2 justify-content-between align-items-center">
                <a class="btn btn-outline-secondary skuad-touch-button" href="{{ route('student.attendance.index') }}"><i class="bi bi-arrow-left"></i> Riwayat saya</a>
                @if($canCheckIn)
                    <form method="POST" action="{{ route('student.attendance.check-in.store', [$attendanceSession, $token]) }}">
                        @csrf
                        <button class="btn btn-primary skuad-touch-button" type="submit" @disabled($record?->checked_in_at)>
                            <i class="bi bi-check2-circle"></i>
                            {{ $record?->checked_in_at ? 'Sudah check-in' : 'Check-in sekarang' }}
                        </button>
                    </form>
                @else
                    <a class="btn btn-primary skuad-touch-button" href="{{ route(auth()->user()->dashboardRouteName()) }}"><i class="bi bi-grid-1x2"></i> Kembali ke dashboard</a>
                @endif
            </div>
        </section>
    </div>
@endsection
