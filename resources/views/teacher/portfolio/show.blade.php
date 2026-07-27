@extends('layouts.dashboard')
@section('title', $item->title.' - Kurasi SKUAD')
@section('breadcrumb', 'Tinjau portofolio')
@section('content')
@include('portfolio._detail', ['teacherMode' => true, 'publicMode' => false])
@endsection
