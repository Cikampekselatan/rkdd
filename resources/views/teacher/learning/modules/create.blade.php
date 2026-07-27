@extends('layouts.dashboard')
@section('title', 'Tambah Modul') @section('breadcrumb', 'Tambah Modul')
@section('content')<x-ui.page-header eyebrow="Pembelajaran program" title="Tambah modul" /><div class="skuad-card p-4 p-lg-5 mx-auto" style="max-width: 62rem"><form method="POST" action="{{ route('teacher.learning.modules.store') }}">@csrf @include('teacher.learning.modules._form')</form></div>@endsection
