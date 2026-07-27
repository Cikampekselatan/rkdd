@extends('layouts.dashboard')
@section('title','Tambah Tahun Ajaran') @section('breadcrumb','Tambah Tahun')
@section('content')<x-ui.page-header eyebrow="Master akademik" title="Tambah tahun ajaran" description="Tahun pertama otomatis menjadi aktif." /><div class="skuad-card p-4 p-lg-5 mx-auto" style="max-width:55rem"><form method="POST" action="{{ route('admin.academic-years.store') }}">@csrf @include('admin.academic-years._form')</form></div>@endsection
