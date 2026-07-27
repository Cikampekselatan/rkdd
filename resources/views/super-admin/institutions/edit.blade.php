@extends('layouts.dashboard')
@section('title', 'Edit Lembaga RKDD')
@section('breadcrumb', 'Edit Lembaga')
@section('content')
<x-ui.page-header eyebrow="Master platform" title="Edit {{ $institution->name }}" />
<div class="skuad-card p-4 p-lg-5 mx-auto" style="max-width:60rem"><form method="POST" action="{{ route('super-admin.institutions.update', $institution) }}">@csrf @method('PUT') @include('super-admin.institutions._form')</form></div>
@endsection
