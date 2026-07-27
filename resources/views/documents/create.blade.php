@extends('layouts.dashboard')
@section('title', 'Tambah Dokumen') @section('breadcrumb', 'Tambah Dokumen')
@section('content')<x-ui.page-header eyebrow="Document Center" title="Tambah dokumen manual" description="Admin dan guru dapat menambahkan panduan atau dokumen Google Drive sesuai kebutuhan." /><div class="skuad-card p-4 p-lg-5"><form method="POST" action="{{ route('documents.store') }}">@csrf @include('documents._form', ['documentResource' => null])</form></div>@endsection
