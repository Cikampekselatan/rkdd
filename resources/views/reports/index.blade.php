@extends('layouts.dashboard')
@section('title','Pusat Laporan - RKDD')
@section('breadcrumb','Pusat laporan')
@section('content')
<div class="report-page"><section class="report-hero"><div><p>Data yang dapat ditelusuri</p><h1>Laporan program dalam satu pintu.</h1><span>Pilih laporan sesuai kewenanganmu, terapkan filter, lalu cetak dalam format A4.</span></div><i class="bi bi-bar-chart-line"></i></section><div class="report-catalog">@forelse($types as $type)<a href="{{ route('reports.show',$type->value) }}"><span><i class="bi {{ $type->icon() }}"></i></span><div><small>Laporan</small><h2>{{ $type->label() }}</h2><p>{{ $type->description() }}</p></div><i class="bi bi-arrow-up-right"></i></a>@empty<div class="report-empty"><h2>Tidak ada laporan yang tersedia</h2><p>Role ini belum memiliki akses ke jenis laporan.</p></div>@endforelse</div></div>
@endsection
