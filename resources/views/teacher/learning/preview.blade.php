@extends('layouts.dashboard')
@section('title', 'Preview Pertemuan') @section('breadcrumb', 'Preview')
@section('content')<div class="learning-preview-bar"><div><x-ui.badge variant="warning">Mode preview guru</x-ui.badge><span>Siswa hanya dapat melihat setelah publish.</span></div><x-ui.button :href="route('teacher.learning.sessions.edit', $learningSession)" variant="outline" icon="bi-pencil">Kembali mengedit</x-ui.button></div>@include('learning._session-content')@endsection
