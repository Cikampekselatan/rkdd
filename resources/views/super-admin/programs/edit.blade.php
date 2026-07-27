@extends('layouts.dashboard')
@section('title', 'Edit Program RKDD')
@section('breadcrumb', 'Edit Program')
@section('content')
<x-ui.page-header eyebrow="Master platform" title="Edit {{ $program->name }}" />
<div class="skuad-card p-4 p-lg-5 mx-auto" style="max-width:60rem"><form method="POST" action="{{ route('super-admin.programs.update', $program) }}" enctype="multipart/form-data">@csrf @method('PUT') @include('super-admin.programs._form')</form></div>
@endsection
