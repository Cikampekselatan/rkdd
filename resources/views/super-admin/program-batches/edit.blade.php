@extends('layouts.dashboard')
@section('title', 'Edit Batch Program')
@section('breadcrumb', 'Edit Batch')
@section('content')
<x-ui.page-header eyebrow="Master platform" title="Edit {{ $programBatch->name }}" />
<div class="skuad-card p-4 p-lg-5 mx-auto" style="max-width:65rem"><form method="POST" action="{{ route('super-admin.program-batches.update', $programBatch) }}">@csrf @method('PUT') @include('super-admin.program-batches._form')</form></div>
@endsection
