@extends('layouts.dashboard')
@section('title', 'Tambah Program RKDD')
@section('breadcrumb', 'Tambah Program')
@section('content')
<x-ui.page-header eyebrow="Master platform" title="Tambah program RKDD" description="Program adalah jenis kegiatan besarnya. Pelaksanaan di sekolah/lembaga berbeda dibuat melalui menu Batch." />
<div class="skuad-card p-4 p-lg-5 mx-auto" style="max-width:60rem"><form method="POST" action="{{ route('super-admin.programs.store') }}" enctype="multipart/form-data">@csrf @include('super-admin.programs._form')</form></div>
@endsection
