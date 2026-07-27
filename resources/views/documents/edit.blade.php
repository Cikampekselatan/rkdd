@extends('layouts.dashboard')
@section('title', 'Edit Dokumen') @section('breadcrumb', 'Edit Dokumen')
@section('content')<x-ui.page-header eyebrow="Document Center" :title="'Edit '.$documentResource->title" /><div class="skuad-card p-4 p-lg-5"><form method="POST" action="{{ route('documents.update', $documentResource) }}">@csrf @method('PUT') @include('documents._form')</form></div>@endsection
