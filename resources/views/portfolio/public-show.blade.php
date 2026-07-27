@extends('layouts.app')
@section('title',$item->title.' - Showcase SKUAD')
@section('content')
<main class="container py-5">@include('portfolio._detail', ['teacherMode' => false, 'publicMode' => true])</main>
@endsection
