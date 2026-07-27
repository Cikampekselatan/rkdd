@extends('layouts.dashboard')
@section('title','Edit Tahun Ajaran') @section('breadcrumb','Edit Tahun')
@section('content')<x-ui.page-header eyebrow="Master akademik" title="Edit {{ $academicYear->name }}" /><div class="skuad-card p-4 p-lg-5 mx-auto" style="max-width:55rem"><form method="POST" action="{{ route('admin.academic-years.update',$academicYear) }}">@csrf @method('PUT') @include('admin.academic-years._form')</form></div>@endsection
