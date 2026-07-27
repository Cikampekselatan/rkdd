@extends('layouts.dashboard')
@section('title','Pengumuman - SKUAD')
@section('breadcrumb','Pengumuman')
@section('content')
<div class="interaction-page">
    <section class="interaction-hero announcement-hero"><div><p>Informasi terarah</p><h1>Yang penting, hadir tepat waktu.</h1><span>Pengumuman kelas, pertemuan, dan sekolah dalam satu ruang yang tenang.</span></div>@can('create', \App\Models\Announcement::class)<a class="btn btn-warning btn-lg" href="{{ route('teacher.announcements.create') }}"><i class="bi bi-plus-lg"></i> Buat pengumuman</a>@endcan</section>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    <div class="announcement-list">@forelse($items as $item)<a class="announcement-row priority-{{ $item->priority->value }} {{ $item->is_read ? 'is-read' : '' }}" href="{{ route('interactions.announcements.show',$item) }}"><span class="announcement-icon"><i class="bi bi-{{ $item->priority->value === 'urgent' ? 'exclamation-triangle' : ($item->is_pinned ? 'pin-angle' : 'megaphone') }}"></i></span><div><small>{{ $item->priority->label() }} · {{ $item->audience->label() }}</small><h2>{{ $item->title }}</h2><p>{{ Str::limit($item->body,130) }}</p><em>{{ $item->published_at?->diffForHumans() ?? 'Draf' }} · {{ $item->author->name }}</em></div>@if(!$item->is_read && auth()->user()->hasRole(\App\Enums\RoleSlug::Student))<b>Baru</b>@endif</a>@empty<div class="interaction-empty"><i class="bi bi-megaphone"></i><h2>Belum ada pengumuman</h2><p>Informasi baru akan muncul sesuai kelas dan pertemuanmu.</p></div>@endforelse</div>{{ $items->links() }}
</div>
@endsection
