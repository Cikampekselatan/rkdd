@extends('layouts.dashboard')
@section('title', 'Edit Kelompok/Angkatan')
@section('breadcrumb', 'Edit Kelompok')
@section('content')
<x-ui.page-header eyebrow="Keanggotaan ekstrakurikuler" :title="'Edit '.$schoolClass->name" />
<div class="skuad-card p-4 p-lg-5 mx-auto" style="max-width:60rem"><form method="POST" action="{{ route('admin.classes.update', $schoolClass) }}">@csrf @method('PUT') @include('admin.classes._form')</form></div>
@endsection
