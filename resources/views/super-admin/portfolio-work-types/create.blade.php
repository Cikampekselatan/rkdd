@extends('layouts.dashboard')
@section('title', 'Tambah Jenis Karya')
@section('breadcrumb', 'Tambah Jenis Karya')
@section('content')
<x-ui.page-header eyebrow="Master portofolio" title="Tambah jenis karya" description="Tambahkan opsi jenis karya untuk program tertentu." />
<div class="skuad-card p-4 p-lg-5 mx-auto" style="max-width:60rem"><form method="POST" action="{{ route('super-admin.portfolio-work-types.store') }}">@csrf @include('super-admin.portfolio-work-types._form')</form></div>
@endsection
