@extends('layouts.dashboard')

@section('title', $topic->title.' - Forum SKUAD')
@section('breadcrumb', 'Diskusi kelas')

@section('content')
    <div class="interaction-page">
        <article class="topic-opening {{ $topic->is_hidden ? 'topic-hidden' : '' }}">
            <header>
                <div class="d-flex align-items-start gap-3">
                    <x-ui.avatar :name="$topic->author->name" :user="$topic->author" size="md" />
                    <div>
                        <small>{{ $topic->schoolClass->name }}{{ $topic->learningSession ? ' · '.$topic->learningSession->title : '' }}</small>
                        <h1>{{ $topic->title }}</h1>
                        <span>{{ $topic->author->name }} · {{ $topic->created_at->diffForHumans() }}</span>
                    </div>
                </div>
                <div class="topic-badges">
                    @if($topic->is_pinned)<b>Disematkan</b>@endif
                    <b>{{ $topic->status->value === 'open' ? 'Terbuka' : 'Ditutup' }}</b>
                </div>
            </header>
            <p>{{ $topic->is_hidden && ! auth()->user()->can('moderate', $topic) ? 'Topik disembunyikan oleh moderator.' : $topic->body }}</p>
        </article>

        @can('moderate', $topic)
            <form class="moderation-bar" method="POST" action="{{ route('teacher.discussions.moderate', $topic) }}">
                @csrf
                @method('PATCH')
                <button class="btn btn-outline-primary" name="action" value="pin"><i class="bi bi-pin-angle"></i> {{ $topic->is_pinned ? 'Lepas pin' : 'Pin' }}</button>
                <button class="btn btn-outline-warning" name="action" value="close"><i class="bi bi-lock"></i> {{ $topic->status->value === 'open' ? 'Tutup' : 'Buka' }}</button>
                <button class="btn btn-outline-danger" name="action" value="hide"><i class="bi bi-eye-slash"></i> {{ $topic->is_hidden ? 'Tampilkan' : 'Sembunyikan' }}</button>
            </form>
        @endcan

        <section class="discussion-thread">
            <h2>{{ $topic->posts->sum(fn ($post) => 1 + $post->replies->count()) }} tanggapan</h2>

            @forelse($topic->posts as $post)
                <article class="discussion-post {{ $post->is_hidden ? 'post-hidden' : '' }}">
                    <x-ui.avatar :name="$post->author->name" :user="$post->author" size="sm" />
                    <div>
                        <header>
                            <strong>{{ $post->author->name }}</strong>
                            <small>{{ $post->created_at->diffForHumans() }}</small>
                        </header>
                        <p>{{ $post->is_hidden ? 'Pesan disembunyikan oleh moderator.' : $post->body }}</p>

                        <div class="post-actions">
                            @if(! $post->is_hidden && $topic->status->value === 'open')
                                <button type="button" data-bs-toggle="collapse" data-bs-target="#reply-{{ $post->id }}">Balas</button>
                            @endif
                            @if($post->user_id !== auth()->id() && ! $post->is_hidden)
                                <button type="button" data-bs-toggle="collapse" data-bs-target="#report-{{ $post->id }}">Laporkan</button>
                            @endif
                            @can('moderate', $post)
                                <form method="POST" action="{{ route('teacher.discussion-posts.moderate', $post) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button>{{ $post->is_hidden ? 'Tampilkan' : 'Sembunyikan' }}@if($post->reports->whereNull('resolved_at')->count()) · {{ $post->reports->whereNull('resolved_at')->count() }} laporan @endif</button>
                                </form>
                            @endcan
                        </div>

                        <form class="collapse reply-form" id="reply-{{ $post->id }}" method="POST" action="{{ route('interactions.discussions.posts.store', $topic) }}">
                            @csrf
                            <input type="hidden" name="parent_id" value="{{ $post->id }}">
                            <textarea class="form-control" name="body" rows="2" required placeholder="Tulis balasan yang membantu..."></textarea>
                            <button class="btn btn-sm btn-primary">Kirim balasan</button>
                        </form>

                        <form class="collapse reply-form" id="report-{{ $post->id }}" method="POST" action="{{ route('interactions.discussions.posts.report', $post) }}">
                            @csrf
                            <input class="form-control" name="reason" required minlength="5" placeholder="Alasan laporan">
                            <button class="btn btn-sm btn-outline-danger">Kirim laporan</button>
                        </form>

                        @foreach($post->replies as $reply)
                            <article class="discussion-reply {{ $reply->is_hidden ? 'post-hidden' : '' }}">
                                <x-ui.avatar :name="$reply->author->name" :user="$reply->author" size="xs" />
                                <div>
                                    <header>
                                        <strong>{{ $reply->author->name }}</strong>
                                        <small>{{ $reply->created_at->diffForHumans() }}</small>
                                    </header>
                                    <p>{{ $reply->is_hidden ? 'Pesan disembunyikan oleh moderator.' : $reply->body }}</p>
                                    @can('moderate', $reply)
                                        <form method="POST" action="{{ route('teacher.discussion-posts.moderate', $reply) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button class="btn btn-sm btn-link">{{ $reply->is_hidden ? 'Tampilkan' : 'Sembunyikan' }}</button>
                                        </form>
                                    @endcan
                                </div>
                            </article>
                        @endforeach
                    </div>
                </article>
            @empty
                <div class="interaction-empty"><p>Belum ada tanggapan. Jadilah yang pertama membantu.</p></div>
            @endforelse
        </section>

        @if($topic->status->value === 'open')
            <form class="discussion-composer" method="POST" action="{{ route('interactions.discussions.posts.store', $topic) }}">
                @csrf
                <textarea class="form-control" name="body" rows="4" required placeholder="Tulis tanggapan yang sopan, jelas, dan relevan..."></textarea>
                <button class="btn btn-primary"><i class="bi bi-send"></i> Kirim tanggapan</button>
            </form>
        @else
            <div class="alert alert-secondary"><i class="bi bi-lock"></i> Diskusi telah ditutup oleh pembina.</div>
        @endif
    </div>
@endsection
