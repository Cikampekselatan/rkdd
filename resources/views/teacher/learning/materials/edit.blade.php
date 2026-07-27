@extends('layouts.dashboard')
@section('title', 'Edit Materi') @section('breadcrumb', 'Edit Materi')
@section('content')<x-ui.page-header eyebrow="Materi pembelajaran" :title="'Edit '.$learningMaterial->title" /><div class="skuad-card p-4 p-lg-5 mx-auto" style="max-width: 68rem"><form method="POST" action="{{ route('teacher.learning.materials.update', $learningMaterial) }}">@csrf @method('PUT') @include('teacher.learning.materials._form')</form></div>@endsection
