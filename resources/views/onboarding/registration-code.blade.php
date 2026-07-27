@extends('layouts.auth')

@section('title', 'Kode Pendaftaran - SKUAD Learning Hub')

@section('content')
    <main class="skuad-auth-message-shell">
        <section class="skuad-auth-message-card">
            <span class="skuad-auth-message-icon"><i class="bi bi-key"></i></span>
            <p class="skuad-eyebrow">Langkah 2 dari 3</p>
            <h1>Masukkan kode pendaftaran.</h1>
            <p>Kode diberikan oleh pengelola program dan hanya berlaku sesuai periode serta batas penggunaan yang ditentukan. Setelah kode valid, kamu akan mengisi profil awal, kelas/lembaga asal bila ada, kelompok/angkatan program, wali/kontak, perangkat, minat, dan persetujuan.</p>

            <form class="text-start mt-4" method="POST" action="{{ route('onboarding.registration-code.store') }}">
                @csrf
                <label class="form-label" for="code">Kode pendaftaran</label>
                <input class="form-control form-control-lg text-uppercase text-center registration-code-input @error('code') is-invalid @enderror" id="code" name="code" value="{{ old('code') }}" maxlength="64" autocomplete="one-time-code" autofocus required placeholder="SKUAD-XXXXX-XXXXX-XXXXX-XXXXX">
                @error('code')<div class="invalid-feedback text-center">{{ $message }}</div>@enderror
                <x-ui.button class="w-100 mt-3" type="submit" icon="bi-shield-check">Validasi kode</x-ui.button>
            </form>

            <a class="skuad-auth-back" href="{{ route('student.onboarding.pending') }}"><i class="bi bi-arrow-left"></i> Kembali</a>
        </section>
    </main>
@endsection
