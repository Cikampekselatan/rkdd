@extends('layouts.dashboard')

@section('title', 'Buat Kode Pendaftaran - SKUAD Learning Hub')
@section('breadcrumb', 'Buat Kode')

@section('content')
    <x-ui.page-header eyebrow="Pendaftaran siswa" title="Buat kode pendaftaran" description="Kode dibuat otomatis dengan entropy tinggi dan hanya ditampilkan satu kali." />
    <div class="skuad-card p-4 p-lg-5 mx-auto" style="max-width: 60rem;">
        <form method="POST" action="{{ route('admin.registration-codes.store') }}">
            @csrf
            @include('admin.registration-codes._form')
        </form>
    </div>
@endsection
