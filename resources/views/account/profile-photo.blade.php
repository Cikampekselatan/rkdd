@extends('layouts.dashboard')

@section('title', 'Profil Saya - SKUAD Learning Hub')
@section('breadcrumb', 'Profil Saya')

@section('content')
    <div class="skuad-card p-4 p-lg-5 mx-auto" style="max-width: 54rem">
        <x-ui.page-header
            eyebrow="Akun"
            title="Foto profil saya"
            description="Upload foto wajah/identitas akun. Sistem akan otomatis mengubah ukuran dan mengompresi foto agar maksimal 500 KB."
        />

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="row g-4 align-items-center">
            <div class="col-md-4 text-center">
                <x-ui.avatar :name="$user->name" :user="$user" size="xl" status="online" class="mx-auto mb-3" />
                <h2 class="h5 mb-1">{{ $user->name }}</h2>
                <p class="text-secondary mb-0">{{ $user->email }}</p>
            </div>

            <div class="col-md-8">
                <form method="POST" action="{{ route('account.profile-photo.update') }}" enctype="multipart/form-data" class="d-grid gap-3">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="form-label" for="photo">Pilih foto</label>
                        <input class="form-control @error('photo') is-invalid @enderror" id="photo" name="photo" type="file" accept="image/jpeg,image/png,image/webp" required>
                        <div class="form-text">Format JPG, PNG, atau WebP. Maksimal upload awal 8 MB; hasil simpan dikompresi maksimal 500 KB.</div>
                        @error('photo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="d-flex flex-wrap gap-2">
                        <button class="btn btn-primary" type="submit"><i class="bi bi-upload"></i> Simpan foto</button>
                        <a class="btn btn-outline-secondary" href="{{ route($user->dashboardRouteName()) }}">Kembali ke dashboard</a>
                    </div>
                </form>

                @if($user->profile_photo_path)
                    <form method="POST" action="{{ route('account.profile-photo.destroy') }}" class="mt-3" onsubmit="return confirm('Hapus foto profil saat ini?')">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-outline-danger" type="submit"><i class="bi bi-trash"></i> Hapus foto profil</button>
                    </form>
                @endif
            </div>
        </div>
    </div>
@endsection
