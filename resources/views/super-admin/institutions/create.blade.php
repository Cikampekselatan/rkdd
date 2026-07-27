@extends('layouts.dashboard')
@section('title', 'Tambah Lembaga RKDD')
@section('breadcrumb', 'Tambah Lembaga')
@section('content')
<x-ui.page-header eyebrow="Master platform" title="Tambah lembaga/penyelenggara" description="Lembaga bisa sekolah, RKDD, komunitas, organisasi, atau mitra pelatihan." />
<div class="skuad-card p-4 p-lg-5 mx-auto" style="max-width:60rem"><form method="POST" action="{{ route('super-admin.institutions.store') }}">@csrf @include('super-admin.institutions._form')</form></div>
@endsection
