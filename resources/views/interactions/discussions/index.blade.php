@extends('layouts.dashboard')

@section('title', 'Forum Diskusi - SKUAD')
@section('breadcrumb', 'Forum diskusi')

@section('content')
    <div class="interaction-page">
        <section class="interaction-hero discussion-hero">
            <div>
                <p>Forum belajar aman</p>
                <h1>Bertanya, menanggapi, bertumbuh.</h1>
                <span>Diskusi terbuka di dalam kelas—tanpa pesan pribadi dan tanpa percakapan tersembunyi.</span>
            </div>
            <button class="btn btn-warning btn-lg" data-bs-toggle="collapse" data-bs-target="#topicComposer"><i class="bi bi-chat-square-text"></i> Topik baru</button>
        </section>

        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

        <div class="collapse {{ $errors->any() ? 'show' : '' }}" id="topicComposer">
            <form class="topic-composer" method="POST" action="{{ route('interactions.discussions.store') }}">
                @csrf
                @if(auth()->user()->isStaff())
                    <select class="form-select" name="class_id" required>
                        <option value="">Pilih kelas</option>
                        @foreach($classes as $class)
                            <option value="{{ $class->id }}">{{ $class->name }}</option>
                        @endforeach
                    </select>
                @endif
                <select class="form-select" name="learning_session_id">
                    <option value="">Diskusi umum kelas</option>
                    @foreach($sessions as $session)
                        <option value="{{ $session->id }}">P{{ $session->session_number }} · {{ $session->title }}</option>
                    @endforeach
                </select>
                <input class="form-control" name="title" placeholder="Apa yang ingin didiskusikan?" required>
                <textarea class="form-control" name="body" rows="4" placeholder="Berikan konteks agar teman dan pembina mudah membantu." required></textarea>
                <button class="btn btn-primary">Buat topik</button>
            </form>
        </div>

        <div class="topic-list">
            @forelse($topics as $topic)
                <a class="topic-row {{ $topic->is_hidden ? 'topic-hidden' : '' }}" href="{{ route('interactions.discussions.show', $topic) }}">
                    <x-ui.avatar :name="$topic->author->name" :user="$topic->author" size="md" />
                    <div>
                        <small>{{ $topic->schoolClass->name }}{{ $topic->learningSession ? ' · '.$topic->learningSession->title : '' }}</small>
                        <h2>{{ $topic->title }}</h2>
                        <p>{{ Str::limit($topic->body, 120) }}</p>
                        <em>{{ $topic->author->name }} · {{ $topic->updated_at->diffForHumans() }}</em>
                    </div>
                    <div class="topic-stats">
                        @if($topic->is_pinned)<b><i class="bi bi-pin-angle"></i></b>@endif
                        @if($topic->status->value === 'closed')<b><i class="bi bi-lock"></i></b>@endif
                        <span>{{ $topic->posts_count }} balasan</span>
                        @if($topic->reports_count)<strong>{{ $topic->reports_count }} laporan</strong>@endif
                    </div>
                </a>
            @empty
                <div class="interaction-empty"><i class="bi bi-chat-square-dots"></i><h2>Belum ada topik</h2><p>Mulai pertanyaan pertama untuk kelasmu.</p></div>
            @endforelse
        </div>

        {{ $topics->links() }}
    </div>
@endsection
