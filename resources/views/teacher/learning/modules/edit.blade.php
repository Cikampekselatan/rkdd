@extends('layouts.dashboard')
@section('title', 'Edit Modul') @section('breadcrumb', 'Edit Modul')
@section('content')<x-ui.page-header eyebrow="Pembelajaran program" :title="'Edit '.$learningModule->title" /><div class="skuad-card p-4 p-lg-5 mx-auto" style="max-width: 62rem"><form method="POST" action="{{ route('teacher.learning.modules.update', $learningModule) }}">@csrf @method('PUT') @include('teacher.learning.modules._form')</form></div>@endsection
