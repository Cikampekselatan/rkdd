@extends('layouts.dashboard')

@section('title', 'Edit Kode Pendaftaran - SKUAD Learning Hub')
@section('breadcrumb', 'Edit Kode')

@section('content')
    <x-ui.page-header eyebrow="Pendaftaran siswa" title="Edit {{ $registrationCode->name }}" description="Kode asli tetap tersembunyi. Perubahan hanya berlaku pada aturan penggunaannya." />
    <div class="skuad-card p-4 p-lg-5 mx-auto" style="max-width: 60rem;">
        <form method="POST" action="{{ route('admin.registration-codes.update', $registrationCode) }}">
            @csrf
            @method('PUT')
            @include('admin.registration-codes._form')
        </form>
    </div>
@endsection
