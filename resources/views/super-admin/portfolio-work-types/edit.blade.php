@extends('layouts.dashboard')
@section('title', 'Edit Jenis Karya')
@section('breadcrumb', 'Edit Jenis Karya')
@section('content')
<x-ui.page-header eyebrow="Master portofolio" :title="'Edit '.$workType->name" />
<div class="skuad-card p-4 p-lg-5 mx-auto" style="max-width:60rem"><form method="POST" action="{{ route('super-admin.portfolio-work-types.update', $workType) }}">@csrf @method('PUT') @include('super-admin.portfolio-work-types._form')</form></div>
@endsection
