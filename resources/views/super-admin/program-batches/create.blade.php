@extends('layouts.dashboard')
@section('title', 'Tambah Batch Program')
@section('breadcrumb', 'Tambah Batch')
@section('content')
<x-ui.page-header eyebrow="Master platform" title="Tambah batch/periode program" description="Hubungkan program dengan sekolah/lembaga tertentu dan tentukan sebutan peserta sesuai konteks." />
<div class="skuad-card p-4 p-lg-5 mx-auto" style="max-width:65rem"><form method="POST" action="{{ route('super-admin.program-batches.store') }}">@csrf @include('super-admin.program-batches._form')</form></div>
@endsection
