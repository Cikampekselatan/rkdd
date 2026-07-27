@extends('layouts.dashboard')
@section('title', $item->title.' - Portofolio SKUAD')
@section('breadcrumb', 'Detail portofolio')
@section('content')
@include('portfolio._detail', ['teacherMode' => false, 'publicMode' => false])
@endsection
