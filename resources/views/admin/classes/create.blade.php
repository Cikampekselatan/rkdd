@extends('layouts.dashboard')
@section('title', 'Tambah Kelompok/Angkatan')
@section('breadcrumb', 'Tambah Kelompok')
@section('content')
<x-ui.page-header
    eyebrow="Keanggotaan program"
    :title="'Tambah '.$groupLabel"
    description="Pilih program/periode tujuan pada form. Kelompok yang dibuat akan masuk ke program yang dipilih, bukan harus mengikuti header aktif."
/>
<div class="skuad-card p-4 p-lg-5 mx-auto" style="max-width:60rem"><form method="POST" action="{{ route('admin.classes.store') }}">@csrf @include('admin.classes._form')</form></div>
@endsection
