@extends('layouts.dashboard')
@section('title', $learningSession->title) @section('breadcrumb', 'Pertemuan '.$learningSession->session_number)
@section('content')
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif @error('component')<div class="alert alert-danger">{{ $message }}</div>@enderror
<div class="student-learning-progress-bar"><div><span>Progress pertemuan</span><strong>{{ $progress->progress_percent }}%</strong></div><div class="progress" role="progressbar" aria-valuenow="{{ $progress->progress_percent }}" aria-valuemin="0" aria-valuemax="100"><div class="progress-bar" style="width: {{ $progress->progress_percent }}%"></div></div></div>
@include('learning._session-content')
<section class="learning-completion-panel"><div><p class="skuad-eyebrow">Simpan progress</p><h2>Tandai bagian yang sudah kamu selesaikan</h2><p>Progress berasal dari aktivitas materi, latihan, dan refleksi—bukan sekadar login.</p></div><div class="learning-completion-actions">
@if($learningSession->materials->isNotEmpty())<form method="POST" action="{{ route('student.learning.progress', $learningSession) }}">@csrf<input type="hidden" name="component" value="materials"><x-ui.button type="submit" :variant="$progress->materials_completed_at ? 'secondary' : 'outline'" icon="bi-check-circle">{{ $progress->materials_completed_at ? 'Materi selesai' : 'Selesaikan materi' }}</x-ui.button></form>@endif
@if($learningSession->practice_instructions)<form method="POST" action="{{ route('student.learning.progress', $learningSession) }}">@csrf<input type="hidden" name="component" value="exercise"><x-ui.button type="submit" :variant="$progress->exercise_completed_at ? 'secondary' : 'outline'" icon="bi-check-circle">{{ $progress->exercise_completed_at ? 'Latihan selesai' : 'Selesaikan latihan' }}</x-ui.button></form>@endif
@if($learningSession->reflection_prompt)<form method="POST" action="{{ route('student.learning.progress', $learningSession) }}">@csrf<input type="hidden" name="component" value="reflection"><x-ui.button type="submit" :variant="$progress->reflection_completed_at ? 'secondary' : 'outline'" icon="bi-check-circle">{{ $progress->reflection_completed_at ? 'Refleksi selesai' : 'Selesaikan refleksi' }}</x-ui.button></form>@endif
</div></section>
@endsection
