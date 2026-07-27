@extends('layouts.dashboard')
@section('title', 'Tambah Pertemuan') @section('breadcrumb', 'Tambah Pertemuan')
@section('content')<x-ui.page-header eyebrow="Pembelajaran program" title="Tambah pertemuan manual" description="Tentukan urutan, judul, jadwal, dan materi sesuai kebutuhan program aktif. Jumlah pertemuan tidak dibatasi angka tetap." /><div class="skuad-card p-4 p-lg-5"><form method="POST" action="{{ route('teacher.learning.sessions.store') }}">@csrf @include('teacher.learning.sessions._form')</form></div>@endsection
